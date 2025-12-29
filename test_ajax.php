<?php
// Test Moodle AJAX service directly
require_once('../../config.php');

// Set up page context
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/ollamamcp/test_ajax.php');

echo "<h1>Moodle AJAX Service Test</h1>";

// Test data that the JavaScript would send
$test_data = [
    [
        'methodname' => 'local_ollamamcp_send_message',
        'args' => [
            'message' => 'Hello, respond with just "AJAX TEST"',
            'courseid' => 0
        ]
    ]
];

echo "<h2>Test Data:</h2>";
echo "<pre>" . htmlspecialchars(json_encode($test_data, JSON_PRETTY_PRINT)) . "</pre>";

// Test via curl like the JavaScript would
$ajax_url = $CFG->wwwroot . '/lib/ajax/service.php';

echo "<h2>Testing AJAX Service via cURL:</h2>";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ajax_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($test_data),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>URL: $ajax_url</p>";
echo "<p>HTTP Code: $http_code</p>";
echo "<p>cURL Error: " . ($error ?: 'None') . "</p>";

echo "<h2>Response:</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse and analyze response
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<h2>Parsed Response Analysis:</h2>";
    
    if (isset($data[0]['error'])) {
        echo "<p style='color: red;'>Error in response: " . htmlspecialchars($data[0]['error']) . "</p>";
    }
    
    if (isset($data[0]['data']['response'])) {
        echo "<p style='color: green;'>✓ Success: " . htmlspecialchars($data[0]['data']['response']) . "</p>";
    } else {
        echo "<p style='color: orange;'>No response data found</p>";
    }
} else {
    echo "<p style='color: red;'>Invalid JSON response: " . json_last_error_msg() . "</p>";
}

?>
