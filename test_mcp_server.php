<?php
// Test MCP server connectivity
require_once('../../config.php');

echo "<h1>MCP Server Test</h1>";

// Test different ports
$ports = [8080, 8081, 8082];
$host = '127.0.0.1';

echo "<h2>Testing MCP Server Connectivity</h2>";

foreach ($ports as $port) {
    echo "<h3>Testing port {$port}</h3>";
    
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!$socket) {
        echo "<p style='color: red;'>❌ Failed to create socket</p>";
        continue;
    }
    
    // Set timeout
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 3, 'usec' => 0]);
    
    $result = @socket_connect($socket, $host, $port);
    if ($result) {
        echo "<p style='color: green;'>✅ Connected to {$host}:{$port}</p>";
        
        // Test MCP protocol
        $test_request = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'Test Client',
                    'version' => '1.0.0'
                ]
            ]
        ]);
        
        socket_write($socket, $test_request . "\n");
        $response = socket_read($socket, 2048, PHP_NORMAL_READ);
        
        if ($response) {
            echo "<p>Server response: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No response from server</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Cannot connect to {$host}:{$port}</p>";
    }
    
    socket_close($socket);
}

echo "<h2>Start MCP Server</h2>";
echo "<p>Use this command to start the MCP server:</p>";
echo "<pre>cd c:\\wamp64\\www\\robot\\local\\ollamamcp</pre>";
echo "<pre>C:\\wamp64\\bin\\php\\php8.3.14\\php.exe cli/start_mcp_server_fixed.php --host=localhost --port=8080 --model=llama3.2:latest</pre>";

echo "<h2>Check if Ollama is Running</h2>";
$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';

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

if ($error) {
    echo "<p style='color: red;'>❌ Ollama server error: " . htmlspecialchars($error) . "</p>";
    echo "<p>Make sure Ollama is running with: <code>ollama serve</code></p>";
} else {
    echo "<p style='color: green;'>✅ Ollama server is accessible (HTTP {$http_code})</p>";
}

?>
