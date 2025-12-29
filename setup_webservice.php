<?php
// Complete web service setup
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/weblib.php');

// Check if user has admin capabilities
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h1>Complete Web Service Setup</h1>";

// 1. Enable web services and REST protocol
set_config('enablewebservices', 1);
set_config('webserviceprotocols', 'rest');

echo "<p>✅ Web services enabled</p>";
echo "<p>✅ REST protocol enabled</p>";

// 2. Create a web service
$webservice = new stdClass();
$webservice->name = 'Ollama MCP Service';
$webservice->timecreated = time();
$webservice->timemodified = time();
$webservice->shortname = 'ollamamcp';
$webservice->enabled = 1;
$webservice->downloadfiles = 0;
$webservice->uploadfiles = 0;
$webservice->restrictedusers = 0;  // Add this required field

// Check if service already exists
$existing = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
if ($existing) {
    $webservice->id = $existing->id;
    $DB->update_record('external_services', $webservice);
    echo "<p>✅ Web service updated</p>";
} else {
    $service_id = $DB->insert_record('external_services', $webservice);
    $webservice->id = $service_id;
    echo "<p>✅ Web service created</p>";
}

// 3. Add the external function to the service
$function = new stdClass();
$function->externalserviceid = $webservice->id;
$function->functionname = 'local_ollamamcp_send_message';

$existing_func = $DB->get_record('external_services_functions', [
    'externalserviceid' => $webservice->id,
    'functionname' => 'local_ollamamcp_send_message'
]);

if (!$existing_func) {
    $DB->insert_record('external_services_functions', $function);
    echo "<p>✅ Function added to service</p>";
} else {
    echo "<p>✅ Function already in service</p>";
}

// 4. Create a web service user for the current admin user
$admin_user = $DB->get_record('user', ['username' => 'admin']);
if ($admin_user) {
    // Check if user already has a token for this service
    $existing_token = $DB->get_record_sql("
        SELECT t.* FROM {external_tokens} t
        JOIN {external_services} s ON t.externalserviceid = s.id
        WHERE t.userid = ? AND s.shortname = ?
    ", [$admin_user->id, 'ollamamcp']);
    
    if (!$existing_token) {
        // Create token
        $token = new stdClass();
        $token->token = md5(uniqid('', true));
        $token->externalserviceid = $webservice->id;
        $token->userid = $admin_user->id;
        $token->contextid = $context->id;
        $token->creatorid = $admin_user->id;
        $token->timecreated = time();
        $token->tokentype = 0;  // Add this required field (0 = permanent token)
        
        $DB->insert_record('external_tokens', $token);
        echo "<p>✅ Web service token created for admin user</p>";
    } else {
        echo "<p>✅ Web service token already exists for admin user</p>";
    }
    
    // Authorize the user for the service
    $auth = new stdClass();
    $auth->externalserviceid = $webservice->id;
    $auth->userid = $admin_user->id;
    
    $existing_auth = $DB->get_record('external_services_users', [
        'externalserviceid' => $webservice->id,
        'userid' => $admin_user->id
    ]);
    
    if (!$existing_auth) {
        $DB->insert_record('external_services_users', $auth);
        echo "<p>✅ Admin user authorized for web service</p>";
    } else {
        echo "<p>✅ Admin user already authorized</p>";
    }
}

echo "<h2>✅ Web Service Setup Complete!</h2>";
echo "<p>Service name: Ollama MCP Service</p>";
echo "<p>Short name: ollamamcp</p>";
echo "<p>Status: Enabled</p>";

echo "<h3>Test AI Assistant</h3>";
echo "<p>Now try sending a message: <a href='/local/ollamamcp/'>AI Assistant Chat</a></p>";

?>
