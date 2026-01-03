<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/weblib.php');
require_once(__DIR__.'/lib.php');

// Set up page context
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/local/ollamamcp/manage_webservices.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_ollamamcp') . ' - ' . get_string('webserviceheading', 'local_ollamamcp'));
$PAGE->set_heading(get_string('webserviceheading', 'local_ollamamcp'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_ollamamcp') . ' - ' . get_string('webserviceheading', 'local_ollamamcp'));

// Handle actions
$action = optional_param('action', '', PARAM_ALPHA);
$message = '';
$message_type = 'success';

// Get plugin settings
$service_name = get_config('local_ollamamcp', 'webservicename') ?: 'Ollama MCP Service';
$service_shortname = get_config('local_ollamamcp', 'webserviceshortname') ?: 'ollamamcp';

// 1. Enable Web Services
if ($action === 'enable_webservices') {
    set_config('enablewebservices', 1);
    set_config('webserviceprotocols', 'rest');
    set_config('local_ollamamcp/enablewebservices', 1);
    $message = get_string('enablewebservices', 'local_ollamamcp') . ' ' . get_string('enabled', 'local_ollamamcp');
}

// 2. Register External Function
if ($action === 'register_function') {
    $function = [
        'name' => 'local_ollamamcp_send_message',
        'classname' => 'local_ollamamcp\\external\\send_message',
        'methodname' => 'execute',
        'description' => 'Send message to AI assistant',
        'type' => 'write',
        'capabilities' => '',
        'ajax' => true,
    ];
    
    $existing = $DB->get_record('external_functions', ['name' => $function['name']]);
    if ($existing) {
        $function['id'] = $existing->id;
        $DB->update_record('external_functions', $function);
        $message = 'External function updated';
    } else {
        $DB->insert_record('external_functions', $function);
        $message = 'External function registered';
    }
}

// 3. Create Web Service
if ($action === 'create_service') {
    $webservice = new stdClass();
    $webservice->name = $service_name;
    $webservice->timecreated = time();
    $webservice->timemodified = time();
    $webservice->shortname = $service_shortname;
    $webservice->enabled = 1;
    $webservice->downloadfiles = 0;
    $webservice->uploadfiles = 0;
    $webservice->restrictedusers = 0;

    $existing = $DB->get_record('external_services', ['shortname' => $service_shortname]);
    if ($existing) {
        $webservice->id = $existing->id;
        $DB->update_record('external_services', $webservice);
        $message = 'Web service updated';
    } else {
        $service_id = $DB->insert_record('external_services', $webservice);
        $webservice->id = $service_id;
        $message = 'Web service created';
    }
    
    // Add function to service
    $service_function = new stdClass();
    $service_function->externalserviceid = $webservice->id;
    $service_function->functionname = 'local_ollamamcp_send_message';

    $existing_func = $DB->get_record('external_services_functions', [
        'externalserviceid' => $webservice->id,
        'functionname' => 'local_ollamamcp_send_message'
    ]);

    if (!$existing_func) {
        $DB->insert_record('external_services_functions', $service_function);
    }
}

// 4. Create Web Service User and Token
if ($action === 'create_token') {
    $admin_user = $DB->get_record('user', ['username' => 'admin']);
    if ($admin_user) {
        // Get the web service
        $webservice = $DB->get_record('external_services', ['shortname' => $service_shortname]);
        
        if ($webservice) {
            // Check if token already exists
            $existing_token = $DB->get_record_sql("
                SELECT t.* FROM {external_tokens} t
                JOIN {external_services} s ON t.externalserviceid = s.id
                WHERE t.userid = ? AND s.shortname = ?
            ", [$admin_user->id, $service_shortname]);
            
            if (!$existing_token) {
                // Create token
                $token = new stdClass();
                $token->token = md5(uniqid('', true));
                $token->externalserviceid = $webservice->id;
                $token->userid = $admin_user->id;
                $token->contextid = $context->id;
                $token->creatorid = $admin_user->id;
                $token->timecreated = time();
                $token->tokentype = 0;
                
                $DB->insert_record('external_tokens', $token);
                $message = 'Web service token created for admin user';
            } else {
                $message = 'Web service token already exists';
            }
            
            // Authorize user for service
            $auth = new stdClass();
            $auth->externalserviceid = $webservice->id;
            $auth->userid = $admin_user->id;
            
            $existing_auth = $DB->get_record('external_services_users', [
                'externalserviceid' => $webservice->id,
                'userid' => $admin_user->id
            ]);
            
            if (!$existing_auth) {
                $DB->insert_record('external_services_users', $auth);
                $message .= ' and admin user authorized';
            }
        } else {
            $message = 'Web service not found. Please create the service first.';
            $message_type = 'warning';
        }
    } else {
        $message = 'Admin user not found';
        $message_type = 'error';
    }
}

// 5. Complete Setup (all in one)
if ($action === 'complete_setup') {
    $result = local_ollamamcp_setup_webservice(true);
    if ($result) {
        $message = 'Complete web service setup finished successfully!';
    } else {
        $message = 'Web service setup failed. Check logs for details.';
        $message_type = 'error';
    }
}

// 6. Sync with plugin settings
if ($action === 'sync_settings') {
    $result = local_ollamamcp_setup_webservice(get_config('local_ollamamcp', 'enablewebservices'));
    if ($result) {
        $message = 'Web service synchronized with plugin settings';
    } else {
        $message = 'Synchronization failed. Check logs for details.';
        $message_type = 'error';
    }
}

// Display status message
if ($message) {
    $alert_class = $message_type === 'error' ? 'alert-danger' : ($message_type === 'warning' ? 'alert-warning' : 'alert-success');
    echo html_writer::div($message, 'alert ' . $alert_class);
}

// Plugin Settings Status Section
echo html_writer::start_div('card');
echo html_writer::div('Current Plugin Settings (from settings.php)', 'card-header');
echo html_writer::start_div('card-body');

$plugin_enabled = get_config('local_ollamamcp', 'enabled');
$ws_enabled_plugin = get_config('local_ollamamcp', 'enablewebservices');

echo html_writer::tag('h4', 'Plugin Configuration');
echo html_writer::tag('p', 'Plugin enabled: ' . ($plugin_enabled ? html_writer::tag('span', '✅ Yes', ['style' => 'color: green;']) : html_writer::tag('span', '❌ No', ['style' => 'color: red;'])));
echo html_writer::tag('p', 'Web services enabled in plugin: ' . ($ws_enabled_plugin ? html_writer::tag('span', '✅ Yes', ['style' => 'color: green;']) : html_writer::tag('span', '❌ No', ['style' => 'color: red;'])));
echo html_writer::tag('p', 'Service name: ' . html_writer::tag('strong', $service_name));
echo html_writer::tag('p', 'Service short name: ' . html_writer::tag('strong', $service_shortname));

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Current Status Section
echo html_writer::start_div('card mt-4');
echo html_writer::div('Current Web Service Status', 'card-header');
echo html_writer::start_div('card-body');

// Check current status
$ws_enabled = get_config('core', 'enablewebservices');
$protocols = get_config('core', 'webserviceprotocols');
$service = $DB->get_record('external_services', ['shortname' => $service_shortname]);
$function = $DB->get_record('external_functions', ['name' => 'local_ollamamcp_send_message']);
// Get admin user for token display
$admin_user = $DB->get_record('user', ['username' => 'admin']);
$token = null;
if ($admin_user) {
    // Debug: Show admin user found
    error_log('Admin user found: ' . $admin_user->id . ' (' . $admin_user->username . ')');
    
    // Try to get existing token first
    $token = $DB->get_record_sql("
        SELECT t.* FROM {external_tokens} t
        JOIN {external_services} s ON t.externalserviceid = s.id
        WHERE s.shortname = ? AND t.userid = ?
    ", [$service_shortname, $admin_user->id]);
    
    // Debug: Show token lookup result
    error_log('Token lookup result: ' . ($token ? 'Found token ID: ' . $token->id : 'No token found'));
    
    // If no token exists, try to create one
    if (!$token) {
        // Debug: Attempting to create token
        error_log('No token found, attempting to create token for service: ' . $service_shortname);
        
        // Get the web service
        $webservice = $DB->get_record('external_services', ['shortname' => $service_shortname]);
        
        if ($webservice) {
            // Create token
            $token = new stdClass();
            $token->token = md5(uniqid('', true));
            $token->externalserviceid = $webservice->id;
            $token->userid = $admin_user->id;
            $token->contextid = context_system::instance()->id;
            $token->creatorid = $admin_user->id;
            $token->timecreated = time();
            $token->tokentype = 0;
            
            $token_id = $DB->insert_record('external_tokens', $token);
            
            // Debug: Token creation success
            error_log('Token created successfully: ID ' . $token_id . ' for user ' . $admin_user->id);
        } else {
            // Debug: Service not found
            error_log('Web service not found for shortname: ' . $service_shortname);
        }
    }
} else {
    // Debug: Admin user not found
    error_log('Admin user not found in database');
}

echo html_writer::tag('h4', 'Web Service Configuration');
echo html_writer::tag('p', 'Web services enabled: ' . ($ws_enabled ? html_writer::tag('span', '✅ Yes', ['style' => 'color: green;']) : html_writer::tag('span', '❌ No', ['style' => 'color: red;'])));
echo html_writer::tag('p', 'Available protocols: ' . htmlspecialchars($protocols));

echo html_writer::tag('h4', $service_name . ' Service');
if ($service) {
    echo html_writer::tag('p', 'Service: ' . html_writer::tag('span', '✅ ' . $service->name, ['style' => 'color: green;']));
    echo html_writer::tag('p', 'Short name: ' . $service->shortname);
    echo html_writer::tag('p', 'Status: ' . ($service->enabled ? 'Enabled' : 'Disabled'));
    echo html_writer::tag('p', 'Created: ' . date('Y-m-d H:i:s', $service->timecreated));
} else {
    echo html_writer::tag('p', 'Service: ' . html_writer::tag('span', '❌ Not created', ['style' => 'color: red;']));
}

echo html_writer::tag('h4', 'External Function');
if ($function) {
    echo html_writer::tag('p', 'Function: ' . html_writer::tag('span', '✅ ' . $function->name, ['style' => 'color: green;']));
    echo html_writer::tag('p', 'Class: ' . $function->classname);
    echo html_writer::tag('p', 'Method: ' . $function->methodname);
    echo html_writer::tag('p', 'AJAX: ' . ($function->ajax ? 'Yes' : 'No'));
} else {
    echo html_writer::tag('p', 'Function: ' . html_writer::tag('span', '❌ Not registered', ['style' => 'color: red;']));
}

echo html_writer::tag('h4', 'Web Service Token');
if ($token) {
    echo html_writer::tag('p', 'Token: ' . html_writer::tag('span', '✅ Created', ['style' => 'color: green;']));
    echo html_writer::tag('p', 'Token ID: ' . $token->id);
    echo html_writer::tag('p', 'Created: ' . date('Y-m-d H:i:s', $token->timecreated));
    echo html_writer::tag('p', 'Token: ' . substr($token->token, 0, 20) . '...');
} else {
    echo html_writer::tag('p', 'Token: ' . html_writer::tag('span', '❌ Not created', ['style' => 'color: red;']));
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Action Buttons Section
echo html_writer::start_div('card mt-4');
echo html_writer::div('Web Service Actions', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', 'Plugin Integration');
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link($PAGE->url . '?action=sync_settings', html_writer::tag('button', 'Sync with Plugin Settings', ['class' => 'btn btn-info w-100']));
echo html_writer::end_div();
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_ollamamcp']), html_writer::tag('button', 'Plugin Settings', ['class' => 'btn btn-secondary w-100']));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('hr', '');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Test Section
echo html_writer::start_div('card mt-4');
echo html_writer::div('Test Web Service', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', 'Test the AI Assistant');
echo html_writer::tag('p', 'Once the web service is set up, you can test the AI assistant:');
echo html_writer::link('./mcp_chat.php', html_writer::tag('button', 'Open AI Assistant Chat', ['class' => 'btn btn-primary']));

echo html_writer::tag('h4', 'API Endpoint');
echo html_writer::tag('p', 'Web service endpoint:');
echo html_writer::tag('code', $CFG->wwwroot . '/webservice/rest/server.php?wsprotocol=rest&wstoken=' . ($token ? $token->token : 'YOUR_TOKEN'), ['class' => 'd-block p-2 bg-light']);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo $OUTPUT->footer();
?>
