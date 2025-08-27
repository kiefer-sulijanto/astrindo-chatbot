<?php
// Test OpenAI API connectivity
$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " // <-- Ganti dengan OpenAI API KEY kamu"
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<pre>" . htmlentities($response) . "</pre>";
}
curl_close($ch);
?>
