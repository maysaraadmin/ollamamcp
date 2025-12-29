<?php
// Debug users endpoint
require_once('../../config.php');

echo "<h1>Debug Users API</h1>";

try {
    // Get API client
    $api = new \local_ollamamcp\mcp\client();
    
    echo "<h2>Testing get_moodle_users method:</h2>";
    $users = $api->get_moodle_users(null, 5);
    
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    echo "<h2>Testing get_moodle_info with 'users' type:</h2>";
    $userInfo = $api->get_moodle_info('users');
    
    echo "<pre>";
    print_r($userInfo);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
