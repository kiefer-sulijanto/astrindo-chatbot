<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
header("Content-Type: application/json");

include("db.php");
include("nlu.php");

$apiKey = "YOUR_OPENAI_KEY_HERE";
$data = json_decode(file_get_contents("php://input"), true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(["status" => "error", "message" => "Please enter a message."]);
    exit;
}   

// --- STEP 1: NLU ---
$nluData = runNLU($userMessage, $apiKey);
$intent = $nluData['intent'] ?? null;
$entities = $nluData['entities'] ?? [];

file_put_contents("debug_nlu_output.json", json_encode($nluData));


if (!$intent) {
    echo json_encode(["status" => "error", "message" => "Intent not detected."]);
    exit;
}

// --- STEP 2: Dispatch ke features ---
$featuresPath = "features/{$intent}.php";

if (file_exists($featuresPath)) {
    include($featuresPath);
    $functionName = "handle" . str_replace('_', '', ucwords($intent, '_'));
    

    file_put_contents("debug_chat_log.txt", json_encode([
        "intent" => $intent,
        "featuresPath" => $featuresPath,
        "functionName" => $functionName,
        "entities" => $entities
    ]));
    

    if (function_exists($functionName)) {
        $responseMessage = $functionName($conn, $entities);
        echo json_encode(["status" => "success", "message" => $responseMessage]);
    } else {
        echo json_encode(["status" => "error", "message" => "Handler function not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Feature not implemented."]);
}
?>
