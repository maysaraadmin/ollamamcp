<?php
// Simple Ollama API test
require_once('../../config.php');

echo "<h1>🧪 Ollama API Direct Test</h1>";

$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
$model = get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest';

echo "<p>Testing Ollama API directly...</p>";
echo "<p>URL: " . htmlspecialchars($ollama_url) . "</p>";
echo "<p>Model: " . htmlspecialchars($model) . "</p>";

// Test 1: Simple GET request
echo "<h2>Test 1: GET /api/tags</h2>";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/tags',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ GET Error: " . htmlspecialchars($error) . "</p>";
} else {
    echo "<p style='color: green;'>✅ GET Success (HTTP $http_code)</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
}

// Test 2: POST request to /api/generate
echo "<h2>Test 2: POST /api/generate</h2>";
$test_data = json_encode([
    'model' => $model,
    'prompt' => 'Hello, respond with just "TEST"',
    'stream' => false,
    'options' => [
        'num_predict' => 10,  // Very short response
        'temperature' => 0.1
    ]
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/generate',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $test_data,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ POST Error: " . htmlspecialchars($error) . "</p>";
    echo "<p>This is likely the issue causing the timeout!</p>";
} else {
    echo "<p style='color: green;'>✅ POST Success (HTTP $http_code)</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
}

// Test 3: Try with curl command line equivalent
echo "<h2>Test 3: Command Line Equivalent</h2>";
echo "<p>Try this command in terminal:</p>";
echo "<pre>curl -X POST " . htmlspecialchars($ollama_url . '/api/generate') . " \\
-H 'Content-Type: application/json' \\
-d '{\"model\":\"" . htmlspecialchars($model) . "\",\"prompt\":\"Hello\",\"stream\":false}'</pre>";

?>
