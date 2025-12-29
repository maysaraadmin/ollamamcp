<?php
// Test Ollama connection directly
require_once('../../config.php');

echo "<h1>Ollama Connection Test</h1>";

$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
echo "<p>Testing Ollama at: $ollama_url</p>";

// Test basic connectivity
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/tags',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
echo "<p>cURL Error: " . ($error ?: 'None') . "</p>";

if ($error) {
    echo "<p style='color: red;'>❌ Cannot connect to Ollama server</p>";
    echo "<p>Make sure Ollama is running with: <code>ollama serve</code></p>";
} else {
    echo "<p style='color: green;'>✅ Connected to Ollama</p>";
    echo "<p>Response: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
}

?>
