<?php

function runNLU($userMessage, $apiKey)
{
    $apiUrl = "https://api.openai.com/v1/chat/completions";

    $nluPrompt = "
You are an NLU engine for Digital Approval chatbot. 
Extract INTENT and ENTITIES from the user's message below.

The user may mix Indonesian and English. Translate months if necessary.

Possible INTENTS:
- marketing_total_cost
- marketing_specialist_cost
- marketing_inventory
- purchasing_top_requester
- purchasing_total_request
- purchasing_vendor_city
- hr_top_requester_atk
- hr_top_item_atk
- finance_top_item
- finance_top_sender
- service_summary

Possible ENTITIES:
- specialist (free text name, do not limit to predefined list)
- year (4-digit year, ex: 2023)
- month (number 1-12)
- city (for vendor city)
- brand (free text name, exact match)
- keyword (optional word to match name or code)
- department (free text)
- status (free text)
- solve_type (free text)

Indonesian Month Translation:
- januari = 1
- februari = 2
- maret = 3
- april = 4
- mei = 5
- juni = 6
- juli = 7
- agustus = 8
- september = 9
- oktober = 10
- november = 11
- desember = 12

Your task:
1️⃣ Understand user message.
2️⃣ Identify intent.
3️⃣ Extract entities if available.
4️⃣ Return only strict valid JSON. DO NOT add explanation, greeting, or any text before/after.

EXAMPLES:

User: \"Total activity marketing bulan Mei 2023\"  
Output: { \"intent\": \"marketing_total_cost\", \"entities\": { \"year\": 2023, \"month\": 5 } }

User: \"Biaya marketing specialist Budi Februari 2024\"  
Output: { \"intent\": \"marketing_specialist_cost\", \"entities\": { \"specialist\": \"Budi\", \"year\": 2024, \"month\": 2 } }

User: \"Inventory marketing?\"  
Output: { \"intent\": \"marketing_inventory\", \"entities\": {} }

User: \"Jumlah vendor di Jakarta\" 
Output: { \"intent\": \"purchasing_vendor_city\", \"entities\": { \"city\": \"Jakarta\" } }

User: \"Siapa paling banyak request ATK tahun 2023\"  
Output: { \"intent\": \"hr_top_requester_atk\", \"entities\": { \"year\": 2023 } }

User: \"Item Finance yang paling banyak direquest tahun 2023 \"
Output: { \"intent\": \"finance_top_item\", \"entities\": { \"year\": 2023 } }

User: \"Top sender request item finance di Januari 2023\"
Output: { \"intent\": \"finance_top_sender\", \"entities\": { \"year\": 2023, \"month\": 1 } }

User: \"Show me all service records in Desember 2023\"
Output: { \"intent\": \"finance_top_sender\", \"entities\": { \"year\": 2023, \"month\": 12 } }

User Message: \"$userMessage\"
";

    $payload = [
        "model" => "gpt-4o",
        "messages" => [
            ["role" => "system", "content" => $nluPrompt]
        ],
        "temperature" => 0,
        "max_tokens" => 500,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents("nlu_raw_response.txt", $response);

    if (!$response) return null;

    $result = json_decode($response, true);
    $jsonContent = $result['choices'][0]['message']['content'];

    // --- Strict JSON parse ---
    if (preg_match('/\{.*\}/s', $jsonContent, $matches)) {
        $cleanJson = $matches[0];
        $nluData = json_decode($cleanJson, true);
    } else {
        file_put_contents("nlu_error_log.txt", $jsonContent);
        return null;
    }

    // --- Fallback months from Indonesian words ---
    if (!isset($nluData['entities']['month'])) {
        $bulanMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
        ];
        foreach ($bulanMap as $name => $num) {
            if (stripos($userMessage, $name) !== false) {
                $nluData['entities']['month'] = $num;
                break;
            }
        }
    }

    // --- Fallback: brand keyword matching from userMessage ---
    if (!isset($nluData['entities']['brand'])) {
        $brands = ['ASUS', 'ASUSTOR', 'INTEL', 'QNAP', 'SD', 'SENNHEISER', 'LEXAR', 'WESTERN DIGITAL', 'ASROCK', 'LENOVO', 'SAPPHIRE', 'BYON'];
        foreach ($brands as $b) {
            if (stripos($userMessage, $b) !== false) {
                $nluData['entities']['brand'] = $b;
                break;
            }
        }
    }


    return $nluData;
}
?>
