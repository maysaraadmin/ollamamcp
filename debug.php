<?php
// Debug script to test Ollama connectivity
require_once('../../config.php');
require_once($CFG->libdir.'/externallib.php');

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Ollama MCP Debug</h1>";

// Check if plugin is enabled
$enabled = get_config('local_ollamamcp', 'enabled');
echo "<p>Plugin enabled: " . ($enabled ? 'YES' : 'NO') . "</p>";

if (!$enabled) {
    echo "<p><strong>ERROR: Plugin is disabled!</strong></p>";
    exit;
}

// Check configuration
$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
$default_model = get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest';
$timeout = get_config('local_ollamamcp', 'timeout') ?: 60;

echo "<p>Ollama URL: $ollama_url</p>";
echo "<p>Default model: $default_model</p>";
echo "<p>Timeout: $timeout seconds</p>";

// Test Ollama connectivity
echo "<h2>Testing Ollama Connectivity</h2>";

try {
    // Test basic connectivity
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $ollama_url . '/api/tags',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>cURL Error: $error</p>";
    } else {
        echo "<p style='color: green;'>✓ Connected to Ollama server (HTTP $http_code)</p>";
        
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['models'])) {
            echo "<p>Available models:</p><ul>";
            foreach ($data['models'] as $model) {
                echo "<li>" . htmlspecialchars($model['name']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>Warning: Could not parse models response</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test API class
echo "<h2>Testing API Class</h2>";

try {
    $api = new \local_ollamamcp\api();
    echo "<p style='color: green;'>✓ API class instantiated</p>";
    
    // Test a simple completion
    echo "<p>Testing simple completion...</p>";
    $result = $api->generate_completion('Hello, respond with just "OK"');
    
    if (isset($result['response'])) {
        echo "<p style='color: green;'>✓ API Response: " . htmlspecialchars($result['response']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Invalid API response format</p>";
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>API Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
    // Additional cURL debugging
    echo "<h3>Manual cURL Test</h3>";
    try {
        $test_url = $ollama_url . '/api/generate';
        $test_data = json_encode([
            'model' => $default_model,
            'prompt' => 'Hello, respond with just "OK"',
            'stream' => false
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $test_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
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
        
        echo "<p>Manual cURL Test URL: $test_url</p>";
        echo "<p>HTTP Code: $http_code</p>";
        echo "<p>cURL Error: " . ($error ?: 'None') . "</p>";
        echo "<p>Response: " . htmlspecialchars(substr($response, 0, 500)) . "</p>";
        
    } catch (Exception $e2) {
        echo "<p style='color: red;'>Manual test failed: " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}

// Test external API
echo "<h2>Testing External API</h2>";

try {
    $result = \local_ollamamcp\external\send_message::execute('Hello, respond with just "OK"', 0);
    echo "<p style='color: green;'>✓ External API Response: " . htmlspecialchars($result['response']) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>External API Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Current User Info</h2>";
global $USER;
echo "<p>User ID: " . $USER->id . "</p>";
echo "<p>Username: " . htmlspecialchars($USER->username) . "</p>";
echo "<p>Email: " . htmlspecialchars($USER->email) . "</p>";
echo "<p>Is logged in: " . (isloggedin() ? 'YES' : 'NO') . "</p>";

?>
