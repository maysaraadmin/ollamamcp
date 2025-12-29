<?php
// Manual registration of external function
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Check if user has admin capabilities
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h1>Manual External Function Registration</h1>";

// Define the external function
$function = [
    'name' => 'local_ollamamcp_send_message',
    'classname' => 'local_ollamamcp\\external\\send_message',
    'methodname' => 'execute',
    'description' => 'Send message to AI assistant',
    'type' => 'write',
    'capabilities' => '',
    'ajax' => true,
];

// Check if function already exists
$existing = $DB->get_record('external_functions', ['name' => $function['name']]);

if ($existing) {
    echo "<p style='color: orange;'>⚠️ Function '{$function['name']}' already exists</p>";
    echo "<p>Updating function...</p>";
    
    // Update existing function
    $function['id'] = $existing->id;
    $DB->update_record('external_functions', $function);
    echo "<p style='color: green;'>✓ Function updated</p>";
} else {
    echo "<p>Registering new function...</p>";
    
    // Insert new function
    $id = $DB->insert_record('external_functions', $function);
    echo "<p style='color: green;'>✓ Function registered with ID: $id</p>";
}

// Verify registration
$verify = $DB->get_record('external_functions', ['name' => $function['name']]);
if ($verify) {
    echo "<h2>✅ Registration Successful!</h2>";
    echo "<p>Function name: {$verify->name}</p>";
    echo "<p>Class: {$verify->classname}</p>";
    echo "<p>Method: {$verify->methodname}</p>";
    echo "<p>AJAX enabled: " . ($verify->ajax ? 'Yes' : 'No') . "</p>";
    
    echo "<h3>Test the AI Assistant</h3>";
    echo "<p>Now try sending a message in the chat at: <a href='/local/ollamamcp/'>AI Assistant Chat</a></p>";
} else {
    echo "<p style='color: red;'>❌ Registration failed</p>";
}

?>
