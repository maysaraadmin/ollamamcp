<?php
// Simple test to check Moodle AJAX service
require_once('../../config.php');

// Minimal setup
require_login();
$context = context_system::instance();

echo json_encode([
    'session_test' => 'OK',
    'user_id' => $USER->id,
    'sesskey' => sesskey(),
    'logged_in' => isloggedin(),
    'context_valid' => has_capability('moodle/site:config', $context)
]);
?>
