<?php
// Final comprehensive error check
require_once('../../config.php');

echo "<h1>🔍 Final Error Check</h1>";

$errors_found = [];
$warnings = [];

// 1. Check for any PHP errors in logs
echo "<h2>📋 PHP Error Log Check</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $recent_errors = tail_file($error_log, 10);
    if ($recent_errors) {
        echo "<p style='color: orange;'>⚠️ Recent errors found:</p>";
        echo "<pre>" . htmlspecialchars($recent_errors) . "</pre>";
        $warnings[] = "PHP errors in log file";
    } else {
        echo "<p style='color: green;'>✅ No recent PHP errors</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Error log not accessible</p>";
}

// 2. Test all major components
echo "<h2>🧪 Component Tests</h2>";

// Test API with different prompts
$api_test_prompts = [
    'Simple test: say "TEST1"',
    'Math: what is 2+2?',
    'Greeting: hello world'
];

foreach ($api_test_prompts as $prompt) {
    try {
        $api = new \local_ollamamcp\api();
        $result = $api->generate_completion($prompt);
        if (isset($result['response']) && strlen($result['response']) > 0) {
            echo "<p style='color: green;'>✅ API Test: " . htmlspecialchars(substr($prompt, 0, 20)) . " → " . htmlspecialchars(substr($result['response'], 0, 30)) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ API Test failed: " . htmlspecialchars($prompt) . "</p>";
            $errors_found[] = "API test failed for: " . $prompt;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ API Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        $errors_found[] = "API exception: " . $e->getMessage();
    }
}

// Test HTTP Bridge with different methods
$bridge_tests = [
    ['method' => 'tools/list', 'params' => []],
    ['method' => 'initialize', 'params' => ['protocolVersion' => '2024-11-05']],
    ['method' => 'tools/call', 'params' => ['name' => 'ollama_chat', 'arguments' => ['prompt' => 'Bridge test: say "OK"']]]
];

foreach ($bridge_tests as $test) {
    $test_request = [
        'jsonrpc' => '2.0',
        'id' => rand(1, 1000),
        'method' => $test['method'],
        'params' => $test['params']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
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
        echo "<p style='color: red;'>❌ Bridge Error ({$test['method']}): " . htmlspecialchars($error) . "</p>";
        $errors_found[] = "Bridge error for {$test['method']}: " . $error;
    } else {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($data['error'])) {
                echo "<p style='color: red;'>❌ Bridge returned error ({$test['method']}): " . htmlspecialchars($data['error']['message']) . "</p>";
                $errors_found[] = "Bridge error for {$test['method']}: " . $data['error']['message'];
            } else {
                echo "<p style='color: green;'>✅ Bridge Test: " . htmlspecialchars($test['method']) . " → Success</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Bridge JSON Error ({$test['method']}): " . json_last_error_msg() . "</p>";
            $errors_found[] = "Bridge JSON error for {$test['method']}: " . json_last_error_msg();
        }
    }
}

// 3. Check for missing configurations
echo "<h2>⚙️ Configuration Check</h2>";

$config_checks = [
    'enabled' => 'Plugin enabled',
    'ollamaserver' => 'Ollama server URL',
    'defaultmodel' => 'Default model',
    'timeout' => 'Timeout setting'
];

foreach ($config_checks as $key => $description) {
    $value = get_config('local_ollamamcp', $key);
    if ($value === false || $value === '') {
        echo "<p style='color: orange;'>⚠️ Missing config: {$description}</p>";
        $warnings[] = "Missing configuration: {$description}";
    } else {
        echo "<p style='color: green;'>✅ Config OK: {$description} = " . htmlspecialchars(substr($value, 0, 50)) . "</p>";
    }
}

// 4. Check file integrity
echo "<h2>📁 File Integrity Check</h2>";

$required_files = [
    'classes/mcp/server.php' => 'MCP Server Class',
    'classes/mcp/client.php' => 'MCP Client Class',
    'classes/api.php' => 'API Class',
    'classes/external/send_message.php' => 'External API',
    'cli/start_mcp_server.php' => 'MCP Server CLI',
    'mcp_http_bridge.php' => 'HTTP Bridge',
    'mcp_chat.php' => 'Chat Interface',
    'db/services.php' => 'Web Service Definitions'
];

foreach ($required_files as $file => $description) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        if (filesize($filepath) > 0) {
            echo "<p style='color: green;'>✅ File OK: {$description}</p>";
        } else {
            echo "<p style='color: red;'>❌ File empty: {$description}</p>";
            $errors_found[] = "Empty file: {$file}";
        }
    } else {
        echo "<p style='color: red;'>❌ File missing: {$description}</p>";
        $errors_found[] = "Missing file: {$file}";
    }
}

// 5. Summary
echo "<h2>📊 Summary</h2>";

if (empty($errors_found) && empty($warnings)) {
    echo "<div class='alert alert-success'>";
    echo "<h3>🎉 Perfect! No errors found!</h3>";
    echo "<p>Your MCP Moodle plugin is fully functional and ready to use.</p>";
    echo "</div>";
} else {
    if (!empty($errors_found)) {
        echo "<div class='alert alert-danger'>";
        echo "<h3>❌ Errors Found:</h3>";
        echo "<ul>";
        foreach ($errors_found as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($warnings)) {
        echo "<div class='alert alert-warning'>";
        echo "<h3>⚠️ Warnings:</h3>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li>" . htmlspecialchars($warning) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// Helper function to read last lines of file
function tail_file($filepath, $lines = 10) {
    if (!file_exists($filepath)) return null;
    
    $handle = fopen($filepath, "r");
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = [];

    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        
        if ($beginning) {
            rewind($handle);
        }
        
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
        $linecounter--;
    }
    
    fclose($handle);
    return implode('', array_reverse($text));
}

?>
