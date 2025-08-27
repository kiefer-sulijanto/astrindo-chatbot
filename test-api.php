<?php
// Test OpenAI API connectivity
$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer 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 "
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<pre>" . htmlentities($response) . "</pre>";
}
curl_close($ch);
?>
