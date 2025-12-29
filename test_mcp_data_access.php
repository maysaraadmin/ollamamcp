<?php
// Test MCP access to Moodle data
require_once('../../config.php');

echo "<h1>🔍 MCP Moodle Data Access Test</h1>";

// Test 1: User Data Access
echo "<h2>👤 User Data Access</h2>";

// Test via HTTP Bridge (web interface context)
$test_request = [
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'tools/call',
    'params' => [
        'name' => 'moodle_info',
        'arguments' => [
            'type' => 'user'
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php',
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
    echo "<p style='color: red;'>❌ User Data Error: " . htmlspecialchars($error) . "</p>";
} else {
    $data = json_decode($response, true);
    if (isset($data['result']['content'][0]['text'])) {
        echo "<p style='color: green;'>✅ User Data Accessed:</p>";
        echo "<pre>" . htmlspecialchars($data['result']['content'][0]['text']) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ User Data Failed</p>";
    }
}

// Test 2: Course Data Access
echo "<h2>📚 Course Data Access</h2>";

// Get first course for testing
$courses = get_courses('all', 'c.shortname ASC', 0, 5); // Get first 5 courses
if ($courses) {
    $first_course = reset($courses);
    $courseid = $first_course->id;
    
    echo "<p>Testing with course: " . htmlspecialchars($first_course->fullname) . " (ID: {$courseid})</p>";
    
    $test_request = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'moodle_info',
            'arguments' => [
                'type' => 'course',
                'courseid' => $courseid
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php',
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
        echo "<p style='color: red;'>❌ Course Data Error: " . htmlspecialchars($error) . "</p>";
    } else {
        $data = json_decode($response, true);
        if (isset($data['result']['content'][0]['text'])) {
            echo "<p style='color: green;'>✅ Course Data Accessed:</p>";
            echo "<pre>" . htmlspecialchars($data['result']['content'][0]['text']) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Course Data Failed</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ No courses found to test with</p>";
}

// Test 3: General System Info
echo "<h2>⚙️ General System Info</h2>";

$test_request = [
    'jsonrpc' => '2.0',
    'id' => 3,
    'method' => 'tools/call',
    'params' => [
        'name' => 'moodle_info',
        'arguments' => [
            'type' => 'general'
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php',
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
    echo "<p style='color: red;'>❌ System Info Error: " . htmlspecialchars($error) . "</p>";
} else {
    $data = json_decode($response, true);
    if (isset($data['result']['content'][0]['text'])) {
        echo "<p style='color: green;'>✅ System Info Accessed:</p>";
        echo "<pre>" . htmlspecialchars($data['result']['content'][0]['text']) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ System Info Failed</p>";
    }
}

// Test 4: Available Tools
echo "<h2>🔧 Available MCP Tools</h2>";

$test_request = [
    'jsonrpc' => '2.0',
    'id' => 4,
    'method' => 'tools/list',
    'params' => []
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $CFG->wwwroot . '/local/ollamamcp/mcp_http_bridge.php',
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
    echo "<p style='color: red;'>❌ Tools List Error: " . htmlspecialchars($error) . "</p>";
} else {
    $data = json_decode($response, true);
    if (isset($data['result']['tools'])) {
        echo "<p style='color: green;'>✅ Available Tools:</p>";
        foreach ($data['result']['tools'] as $tool) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0;'>";
            echo "<strong>" . htmlspecialchars($tool['name']) . "</strong><br>";
            echo "<em>" . htmlspecialchars($tool['description']) . "</em>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>❌ Tools List Failed</p>";
    }
}

// Summary
echo "<h2>📊 Summary</h2>";
echo "<div class='alert alert-success'>";
echo "<h3>✅ MCP Can Access:</h3>";
echo "<ul>";
echo "<li><strong>👤 User Data:</strong> Current logged-in user information (ID, username, email)</li>";
echo "<li><strong>📚 Course Data:</strong> Course information (name, shortname, summary) by course ID</li>";
echo "<li><strong>⚙️ System Info:</strong> Moodle version, plugin info, configuration</li>";
echo "<li><strong>🔧 Tools:</strong> ollama_chat (AI responses) and moodle_info (data access)</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 How to Use in Chat</h2>";
echo "<div class='alert alert-info'>";
echo "<p><strong>Try these commands in the chat interface:</strong></p>";
echo "<ul>";
echo "<li><code>who is the current user</code> - Shows logged-in user info</li>";
echo "<li><code>tell me about course [ID]</code> - Shows course details</li>";
echo "<li><code>list courses</code> - AI can access course database</li>";
echo "<li><code>what is my username</code> - Shows your Moodle username</li>";
echo "</ul>";
echo "</div>";

?>
