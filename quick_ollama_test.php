<?php
// Quick Ollama connectivity test
require_once('../../config.php');

echo "<h1>🔧 Ollama Connectivity Check</h1>";

$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
echo "<p>Testing Ollama at: " . htmlspecialchars($ollama_url) . "</p>";

// Test 1: Simple GET request
echo "<h2>Test 1: GET /api/tags</h2>";
$start_time = microtime(true);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/tags',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$time_taken = microtime(true) - $start_time;
curl_close($ch);

echo "<p>Time taken: " . number_format($time_taken, 2) . " seconds</p>";

if ($error) {
    echo "<p style='color: red;'>❌ GET Error: " . htmlspecialchars($error) . "</p>";
} else {
    echo "<p style='color: green;'>✅ GET Success (HTTP $http_code)</p>";
}

// Test 2: POST request with very short prompt
echo "<h2>Test 2: POST /api/generate (short prompt)</h2>";
$start_time = microtime(true);

$test_data = json_encode([
    'model' => 'llama3.2:latest',
    'prompt' => 'Hi',
    'stream' => false,
    'options' => [
        'num_predict' => 5,  // Very short response
        'temperature' => 0.1
    ]
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/generate',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $test_data,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$time_taken = microtime(true) - $start_time;
curl_close($ch);

echo "<p>Time taken: " . number_format($time_taken, 2) . " seconds</p>";

if ($error) {
    echo "<p style='color: red;'>❌ POST Error: " . htmlspecialchars($error) . "</p>";
    echo "<p><strong>This is the issue causing the timeout!</strong></p>";
} else {
    echo "<p style='color: green;'>✅ POST Success (HTTP $http_code)</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 200)) . "</pre>";
}

// Test 3: Check if Ollama is actually running
echo "<h2>Test 3: Ollama Process Check</h2>";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $command = 'tasklist | findstr ollama';
    echo "<p>Running: <code>" . htmlspecialchars($command) . "</code></p>";
    $output = shell_exec($command);
    if (strpos($output, 'ollama') !== false) {
        echo "<p style='color: green;'>✅ Ollama process is running</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Ollama process not found</p>";
        echo "<p>Start Ollama with: <code>ollama serve</code></p>";
    }
} else {
    // Linux/Mac
    $command = 'ps aux | grep ollama';
    echo "<p>Running: <code>" . htmlspecialchars($command) . "</code></p>";
    $output = shell_exec($command);
    if (strpos($output, 'ollama') !== false) {
        echo "<p style='color: green;'>✅ Ollama process is running</p>";
    } else {
        echo "<p style='color: red;'>❌ Ollama process not found</p>";
        echo "<p>Start Ollama with: <code>ollama serve</code></p>";
    }
}

// Test 4: Try direct socket connection
echo "<h2>Test 4: Socket Connection Test</h2>";

$host = 'localhost';
$port = 11434;
$timeout = 5;

$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket) {
    $start_time = microtime(true);
    $result = @socket_connect($socket, $host, $port);
    $time_taken = microtime(true) - $start_time;
    
    if ($result) {
        echo "<p style='color: green;'>✅ Socket connected to {$host}:{$port} (took " . number_format($time_taken, 3) . "s)</p>";
    } else {
        echo "<p style='color: red;'>❌ Socket connection failed to {$host}:{$port}</p>";
    }
    socket_close($socket);
} else {
    echo "<p style='color: red;'>❌ Could not create socket</p>";
}

// Recommendations
echo "<h2>💡 Solutions</h2>";
echo "<div class='alert alert-info'>";
echo "<h4>If POST requests are timing out:</h4>";
echo "<ul>";
echo "<li><strong>Restart Ollama:</strong> <code>ollama serve</code></li>";
echo "<li><strong>Check model availability:</strong> <code>ollama list</code></li>";
echo "<li><strong>Try a different model:</strong> Update plugin settings</li>";
echo "<li><strong>Increase timeout:</strong> Set timeout to 60+ seconds in plugin settings</li>";
echo "<li><strong>Check system resources:</strong> Ollama might be overloaded</li>";
echo "</ul>";
echo "</div>";

?>
