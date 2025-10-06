<?php
// Test API connectivity
$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "$apiKey = getenv('OPENAI_API_KEY');
 "
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
} else {
    echo "<pre>" . htmlentities($response) . "</pre>";
}
curl_close($ch);
?>
