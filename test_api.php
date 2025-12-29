<?php
// Test external API directly
require_once('../../config.php');

// Set up page context
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/ollamamcp/test_api.php');

header('Content-Type: application/json');

try {
    // Test the external API directly
    $result = \local_ollamamcp\external\send_message::execute('Hello, respond with just "API TEST"', 0);
    
    echo json_encode([
        'success' => true,
        'result' => $result,
        'user' => [
            'id' => $USER->id,
            'username' => $USER->username,
            'logged_in' => isloggedin()
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
