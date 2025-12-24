<?php
// =====================================================
// Astrindo Chatbot - chat.php (WITH AI TITLE + BUILD TAG)
// =====================================================

$BUILD = "chatphp_title_build";

// --- CORS + Preflight ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- PHP error settings ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=utf-8");

// ---------- Anti-blank: show fatal errors as JSON ----------
register_shutdown_function(function () use ($BUILD) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode([
            "status" => "fatal",
            "build" => $BUILD,
            "message" => $err["message"],
            "file" => $err["file"],
            "line" => $err["line"],
        ], JSON_PRETTY_PRINT);
    }
});

set_exception_handler(function ($e) use ($BUILD) {
    http_response_code(500);
    echo json_encode([
        "status" => "exception",
        "build" => $BUILD,
        "message" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
    ], JSON_PRETTY_PRINT);
    exit;
});

// ---------- Helpers ----------
function loadEnv(string $path): array {
    if (!file_exists($path)) return [];
    return parse_ini_file($path, false, INI_SCANNER_RAW) ?: [];
}

function getApiKey(): string {
    $env = loadEnv(__DIR__ . "/.env");
    $key = trim($env["OPENAI_API_KEY"] ?? "");
    return trim($key, "\"'");
}

function ensureStorageDir(): string {
    $dir = __DIR__ . "/storage";
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir;
}

function safeLog(string $filename, string $content): void {
    $dir = ensureStorageDir();
    @file_put_contents($dir . "/" . $filename, $content);
}

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

function openaiHealthcheck(string $apiKey): array {
    $ch = curl_init("https://api.openai.com/v1/models");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["ok" => false, "http" => 500, "error" => "cURL error: {$err}"];
    }
    curl_close($ch);

    return ($http === 200)
        ? ["ok" => true, "http" => 200]
        : ["ok" => false, "http" => $http, "error" => json_decode($response, true) ?: $response];
}

function fallbackChat(string $userMessage, string $apiKey): string {
    $apiUrl = "https://api.openai.com/v1/chat/completions";

    $system = <<<SYS
You are Astrindo Digital Approval assistant.
Answer naturally like ChatGPT (friendly, short, helpful).
If user asks something unrelated to Digital Approval reports, still answer normally.
Only mention your Digital Approval reporting capabilities if it helps the user, and keep it brief.
If user asks "sekarang jam berapa", say you can't read system clock and ask timezone/city.
SYS;

    $payload = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => $system],
            ["role" => "user", "content" => $userMessage],
        ],
        "temperature" => 0.7,
        "max_tokens" => 200,
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
        CURLOPT_TIMEOUT => 25
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return "Maaf, aku lagi error. Coba ulang ya.";
    $json = json_decode($response, true);
    $text = $json["choices"][0]["message"]["content"] ?? null;
    return $text ? trim($text) : "Maaf, aku belum bisa jawab itu.";
}

// =====================================================
// 1) Load API Key
// =====================================================
$apiKey = getApiKey();
if ($apiKey === "") {
    respond(500, ["status" => "error", "build" => $BUILD, "message" => "OPENAI_API_KEY missing/empty in .env"]);
}

// =====================================================
// 2) Route by method
// =====================================================
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $hc = openaiHealthcheck($apiKey);
    respond($hc["ok"] ? 200 : 500, $hc["ok"]
        ? ["status" => "SUCCESS ✅", "build" => $BUILD, "message" => "OpenAI API connected", "http" => 200]
        : ["status" => "FAILED ❌", "build" => $BUILD, "http" => $hc["http"], "error" => $hc["error"]]
    );
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, ["status" => "error", "build" => $BUILD, "message" => "Method not allowed. Use GET, POST, or OPTIONS."]);
}

// Read input JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$userMessage = trim($data["message"] ?? "");
if ($userMessage === "") {
    respond(400, ["status" => "error", "build" => $BUILD, "message" => "Please send JSON: {\"message\":\"...\"}"]);
}

// =====================================================
// 3) Includes (DB + NLU)
// =====================================================
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/nlu.php";

// =====================================================
// 4) NLU (title included)
// =====================================================
$nluData  = runNLU($userMessage, $apiKey) ?: [];
$intent   = $nluData["intent"] ?? "smalltalk";
$title    = $nluData["title"] ?? "General Chat";
$entities = $nluData["entities"] ?? [];

safeLog("debug_nlu_output.json", json_encode($nluData, JSON_PRETTY_PRINT));

// =====================================================
// smalltalk / unknown -> fallback chat
// =====================================================
if (!$intent || $intent === "smalltalk") {
    $reply = fallbackChat($userMessage, $apiKey);
    respond(200, [
        "status" => "success",
        "build"  => $BUILD,
        "intent" => "smalltalk",
        "title"  => $title,
        "message" => $reply
    ]);
}

// =====================================================
// 5) Dispatch to features/<intent>.php
// =====================================================
$featureFile = __DIR__ . "/features/{$intent}.php";
if (!file_exists($featureFile)) {
    $reply = fallbackChat($userMessage, $apiKey);
    respond(200, [
        "status" => "success",
        "build"  => $BUILD,
        "intent" => $intent,
        "title"  => $title,
        "message" => $reply
    ]);
}

require_once $featureFile;
$functionName = "handle" . str_replace(' ', '', ucwords(str_replace('_', ' ', $intent)));

if (!function_exists($functionName)) {
    $reply = fallbackChat($userMessage, $apiKey);
    respond(200, [
        "status" => "success",
        "build"  => $BUILD,
        "intent" => $intent,
        "title"  => $title,
        "message" => $reply
    ]);
}

$responseMessage = $functionName($conn, $entities);

respond(200, [
    "status" => "success",
    "build"  => $BUILD,
    "intent" => $intent,
    "title"  => $title,
    "message" => $responseMessage
]);
