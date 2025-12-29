<?php
// Comprehensive MCP Plugin Diagnostic
require_once('../../config.php');

echo "<h1>🔍 MCP Moodle Plugin Diagnostic</h1>";

// Check plugin status
echo "<h2>📋 Plugin Status</h2>";
$plugin_enabled = get_config('local_ollamamcp', 'enabled');
echo "<p>Plugin enabled: " . ($plugin_enabled ? "✅ Yes" : "❌ No") . "</p>";

if (!$plugin_enabled) {
    echo "<p style='color: red;'>❌ Plugin is disabled - enable it first!</p>";
    exit;
}

// Check Ollama configuration
echo "<h2>🔗 Ollama Configuration</h2>";
$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
$default_model = get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest';
$timeout = get_config('local_ollamamcp', 'timeout') ?: 30;

echo "<p>Ollama URL: <code>" . htmlspecialchars($ollama_url) . "</code></p>";
echo "<p>Default model: <code>" . htmlspecialchars($default_model) . "</code></p>";
echo "<p>Timeout: {$timeout} seconds</p>";

// Test Ollama connectivity
echo "<h2>🌐 Ollama Server Test</h2>";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ollama_url . '/api/tags',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ Ollama connection failed: " . htmlspecialchars($error) . "</p>";
    echo "<p>💡 Solution: Start Ollama with <code>ollama serve</code></p>";
} else {
    echo "<p style='color: green;'>✅ Ollama server accessible (HTTP {$http_code})</p>";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($data['models'])) {
        echo "<p>Available models:</p><ul>";
        foreach ($data['models'] as $model) {
            echo "<li>" . htmlspecialchars($model['name']) . "</li>";
        }
        echo "</ul>";
    }
}

// Check API class
echo "<h2>🔧 API Class Test</h2>";
try {
    $api = new \local_ollamamcp\api();
    echo "<p style='color: green;'>✅ API class instantiated</p>";
    
    // Test a simple completion
    echo "<p>Testing simple completion...</p>";
    $result = $api->generate_completion('Hello, respond with just "OK"');
    
    if (isset($result['response'])) {
        echo "<p style='color: green;'>✅ API Response: " . htmlspecialchars($result['response']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Invalid API response format</p>";
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ API Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Check HTTP Bridge
echo "<h2>🌉 HTTP Bridge Test</h2>";
$bridge_url = $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php';

$test_request = [
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'tools/call',
    'params' => [
        'name' => 'ollama_chat',
        'arguments' => [
            'prompt' => 'Hello, respond with just "BRIDGE TEST"',
            'context' => []
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $bridge_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($test_request),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "<p style='color: red;'>❌ HTTP Bridge Error: " . htmlspecialchars($error) . "</p>";
} else {
    echo "<p style='color: green;'>✅ HTTP Bridge accessible (HTTP {$http_code})</p>";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($data['error'])) {
            echo "<p style='color: red;'>❌ Bridge returned error: " . htmlspecialchars($data['error']['message']) . "</p>";
            if (isset($data['error']['code'])) {
                echo "<p>Error code: " . $data['error']['code'] . "</p>";
            }
        } elseif (isset($data['result'])) {
            echo "<p style='color: green;'>✅ Bridge response successful</p>";
            if (isset($data['result']['content'][0]['text'])) {
                echo "<p>Response: " . htmlspecialchars($data['result']['content'][0]['text']) . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Unexpected bridge response format</p>";
            echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON from bridge: " . json_last_error_msg() . "</p>";
    }
}

// Check file permissions
echo "<h2>📁 File Permissions</h2>";
$files_to_check = [
    'classes/mcp/server.php',
    'classes/mcp/client.php', 
    'classes/api.php',
    'classes/external/send_message.php',
    'cli/start_mcp_server.php',
    'mcp_http_bridge.php',
    'mcp_chat.php'
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        if (is_readable($filepath)) {
            echo "<p style='color: green;'>✅ {$file} - readable</p>";
        } else {
            echo "<p style='color: red;'>❌ {$file} - not readable</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ {$file} - missing</p>";
    }
}

// Check PHP extensions
echo "<h2>🐘 PHP Extensions</h2>";
$required_extensions = ['curl', 'json', 'sockets'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ {$ext} extension loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ {$ext} extension missing</p>";
    }
}

// Recommendations
echo "<h2>💡 Recommendations</h2>";
echo "<div class='alert alert-info'>";
echo "<h4>Common Issues and Solutions:</h4>";
echo "<ul>";
echo "<li><strong>Ollama not running:</strong> Run <code>ollama serve</code></li>";
echo "<li><strong>Wrong model:</strong> Check available models with <code>ollama list</code></li>";
echo "<li><strong>Permission issues:</strong> Ensure PHP can access all plugin files</li>";
echo "<li><strong>Timeout issues:</strong> Increase timeout in plugin settings</li>";
echo "<li><strong>Socket errors:</strong> Check if ports 8080-8082 are available</li>";
echo "</ul>";
echo "</div>";

?>
