<?php

function runNLU(string $userMessage, string $apiKey): ?array
{
    $apiUrl = "https://api.openai.com/v1/chat/completions";

    $storageDir = __DIR__ . "/storage";
    if (!is_dir($storageDir)) @mkdir($storageDir, 0775, true);

    // NOTE:
    // We want ChatGPT-like titles: short, specific, summarizing intent (not generic labels).
    $prompt = <<<PROMPT
You are an NLU engine for Astrindo Digital Approval chatbot.

TASK:
1) Choose ONE intent from the list.
2) Extract entities when relevant (year, month, specialist name, city, brand, etc).
3) Generate a short sidebar chat title (2 to 6 words) like ChatGPT.

TITLE RULES (CRITICAL):
- ALWAYS output a title (2 to 6 words).
- No quotes. No emojis.
- Do NOT copy the whole user message.
- The title must summarize the user's intent in a natural way (ChatGPT sidebar style).
- Avoid generic template titles such as:
  "General Chat", "Greeting", "Quick Hello", "Small Talk", "Casual Chat",
  "Time Question", "Help Request", "Quick Question", "Need Help", "General Inquiry".
- Use Indonesian if the user writes Indonesian; otherwise English.

IMPORTANT DOMAIN RULE:
- Astrindo chatbot ONLY handles:
  marketing, inventory, purchasing, HR, finance, service data
  related to Astrindo internal system.
- If user asks about:
  cars, electronics reviews, sports teams, people, places,
  general knowledge, or brands NOT related to Astrindo products,
  then intent MUST be "smalltalk".
- Use Indonesian if the user writes Indonesian; otherwise English.


INTENTS:
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
- smalltalk

Return ONLY strict JSON:
{ "intent": "...", "title": "...", "entities": { ... } }

User message: "{$userMessage}"
PROMPT;

    $payload = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => $prompt]
        ],
        "temperature" => 0,
        "max_tokens" => 240
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer {$apiKey}"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;

    @file_put_contents($storageDir . "/nlu_raw_response.txt", $response);

    $result  = json_decode($response, true);
    $content = trim($result["choices"][0]["message"]["content"] ?? "");

    // Extract JSON block
    if (!preg_match('/\{.*\}/s', $content, $m)) {
        return ["intent" => "smalltalk", "title" => fallbackTitle($userMessage), "entities" => []];
    }

    $nlu = json_decode($m[0], true);
    if (!is_array($nlu)) {
        return ["intent" => "smalltalk", "title" => fallbackTitle($userMessage), "entities" => []];
    }

    $intent   = trim($nlu["intent"] ?? "");
    $title    = trim($nlu["title"] ?? "");
    $entities = $nlu["entities"] ?? [];

    if ($intent === "") $intent = "smalltalk";
    if (!is_array($entities)) $entities = [];

    // Title cleanup
    $title = preg_replace('/[\r\n]+/', ' ', $title);
    $title = preg_replace('/^"+|"+$/', '', $title);
    $title = preg_replace('/\s{2,}/', ' ', $title);
    $title = trim($title);

    // Hard safety: if AI still returns generic template titles, replace with fallback
    $banned = [
        "general chat",
        "greeting",
        "quick hello",
        "small talk",
        "casual chat",
        "time question",
        "help request",
        "quick question",
        "need help",
        "general inquiry",
    ];
    if ($title === "" || mb_strlen($title) < 2) {
        $title = fallbackTitle($userMessage);
    } else {
        $lower = mb_strtolower($title);
        if (in_array($lower, $banned, true)) {
            $title = fallbackTitle($userMessage);
        }
    }

    if (mb_strlen($title) > 48) $title = mb_substr($title, 0, 48);

    return [
        "intent" => $intent,
        "title" => $title,
        "entities" => $entities
    ];
}

/**
 * Local fallback: if AI output is invalid / too generic, derive a simple intent summary.
 * This keeps titles "natural" and not template-y.
 */
function fallbackTitle(string $userMessage): string
{
    $msg = trim(mb_strtolower($userMessage));

    // Indonesian heuristics (simple, safe)
    if ($msg === "" ) return "Diskusi umum";

    // Intro / greeting
    if (preg_match('/\b(halo|hai|hi|hey|selamat (pagi|siang|sore|malam))\b/u', $msg)) {
        if (preg_match('/\b(saya|aku)\b/u', $msg)) return "Salam perkenalan";
        return "Mulai percakapan";
    }

    // Ask time
    if (preg_match('/\b(jam berapa|pukul berapa|sekarang jam)\b/u', $msg)) {
        return "Cek waktu sekarang";
    }

    // Cost / biaya
    if (preg_match('/\b(biaya|cost|total cost)\b/u', $msg)) {
        return "Cek total biaya";
    }

    // Marketing
    if (preg_match('/\bmarketing\b/u', $msg)) {
        return "Pertanyaan marketing";
    }

    // Purchasing
    if (preg_match('/\b(purchas(e|ing)|pembelian|vendor)\b/u', $msg)) {
        return "Permintaan purchasing";
    }

    // Finance
    if (preg_match('/\bfinance|keuangan\b/u', $msg)) {
        return "Pertanyaan finance";
    }

    // Service
    if (preg_match('/\bservice|servis|rma\b/u', $msg)) {
        return "Laporan service";
    }

    // Default: short summary from first words (2–6 words max)
    // Keep it natural: capitalize first letter
    $words = preg_split('/\s+/u', $msg);
    $words = array_values(array_filter($words, fn($w) => $w !== ""));
    $slice = array_slice($words, 0, 5);
    $title = implode(' ', $slice);
    $title = mb_substr($title, 0, 48);

    // Capitalize first letter
    $title = mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);

    // If still too short
    if (mb_strlen(trim($title)) < 2) return "Diskusi umum";
    return $title;
}
