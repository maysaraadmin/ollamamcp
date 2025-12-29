<?php
// Enable web services
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Check if user has admin capabilities
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h1>Enable Web Services</h1>";

// Enable web services
set_config('enablewebservices', 1);

// Enable REST protocol
set_config('webserviceprotocols', 'rest');

echo "<p style='color: green;'>✅ Web services enabled</p>";
echo "<p style='color: green;'>✅ REST protocol enabled</p>";

// Verify settings
$ws_enabled = get_config('core', 'enablewebservices');
$protocols = get_config('core', 'webserviceprotocols');

echo "<h2>Current Settings:</h2>";
echo "<p>Web services enabled: " . ($ws_enabled ? 'Yes' : 'No') . "</p>";
echo "<p>Available protocols: " . htmlspecialchars($protocols) . "</p>";

echo "<h3>Test AI Assistant</h3>";
echo "<p>Now try sending a message in chat: <a href='/local/ollamamcp/'>AI Assistant Chat</a></p>";

?>
