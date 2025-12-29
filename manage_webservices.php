<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/weblib.php');

// Set up page context
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/local/ollamamcp/manage_webservices.php');
$PAGE->set_context($context);
$PAGE->set_title('Ollama MCP - Web Service Management');
$PAGE->set_heading('Web Service Management');

echo $OUTPUT->header();
echo $OUTPUT->heading('Ollama MCP Web Service Management');

// Handle actions
$action = optional_param('action', '', PARAM_ALPHA);
$message = '';

// 1. Enable Web Services
if ($action === 'enable_webservices') {
    set_config('enablewebservices', 1);
    set_config('webserviceprotocols', 'rest');
    $message = 'Web services and REST protocol enabled';
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
    $webservice->name = 'Ollama MCP Service';
    $webservice->timecreated = time();
    $webservice->timemodified = time();
    $webservice->shortname = 'ollamamcp';
    $webservice->enabled = 1;
    $webservice->downloadfiles = 0;
    $webservice->uploadfiles = 0;
    $webservice->restrictedusers = 0;

    $existing = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
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
        $webservice = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
        
        if ($webservice) {
            // Check if token already exists
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
        }
    }
}

// 5. Complete Setup (all in one)
if ($action === 'complete_setup') {
    // Enable web services and REST protocol
    set_config('enablewebservices', 1);
    set_config('webserviceprotocols', 'rest');
    
    // Create web service
    $webservice = new stdClass();
    $webservice->name = 'Ollama MCP Service';
    $webservice->timecreated = time();
    $webservice->timemodified = time();
    $webservice->shortname = 'ollamamcp';
    $webservice->enabled = 1;
    $webservice->downloadfiles = 0;
    $webservice->uploadfiles = 0;
    $webservice->restrictedusers = 0;

    $existing = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
    if ($existing) {
        $webservice->id = $existing->id;
        $DB->update_record('external_services', $webservice);
    } else {
        $service_id = $DB->insert_record('external_services', $webservice);
        $webservice->id = $service_id;
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
    
    // Register external function
    $function = [
        'name' => 'local_ollamamcp_send_message',
        'classname' => 'local_ollamamcp\\external\\send_message',
        'methodname' => 'execute',
        'description' => 'Send message to AI assistant',
        'type' => 'write',
        'capabilities' => '',
        'ajax' => true,
    ];
    
    $existing_func = $DB->get_record('external_functions', ['name' => $function['name']]);
    if ($existing_func) {
        $function['id'] = $existing_func->id;
        $DB->update_record('external_functions', $function);
    } else {
        $DB->insert_record('external_functions', $function);
    }
    
    // Create token and authorize admin user
    $admin_user = $DB->get_record('user', ['username' => 'admin']);
    if ($admin_user) {
        $existing_token = $DB->get_record_sql("
            SELECT t.* FROM {external_tokens} t
            JOIN {external_services} s ON t.externalserviceid = s.id
            WHERE t.userid = ? AND s.shortname = ?
        ", [$admin_user->id, 'ollamamcp']);
        
        if (!$existing_token) {
            $token = new stdClass();
            $token->token = md5(uniqid('', true));
            $token->externalserviceid = $webservice->id;
            $token->userid = $admin_user->id;
            $token->contextid = $context->id;
            $token->creatorid = $admin_user->id;
            $token->timecreated = time();
            $token->tokentype = 0;
            
            $DB->insert_record('external_tokens', $token);
        }
        
        $auth = new stdClass();
        $auth->externalserviceid = $webservice->id;
        $auth->userid = $admin_user->id;
        
        $existing_auth = $DB->get_record('external_services_users', [
            'externalserviceid' => $webservice->id,
            'userid' => $admin_user->id
        ]);
        
        if (!$existing_auth) {
            $DB->insert_record('external_services_users', $auth);
        }
    }
    
    $message = 'Complete web service setup finished successfully!';
}

// Display status message
if ($message) {
    echo html_writer::div($message, 'alert alert-success');
}

// Current Status Section
echo html_writer::start_div('card');
echo html_writer::div('Current Web Service Status', 'card-header');
echo html_writer::start_div('card-body');

// Check current status
$ws_enabled = get_config('core', 'enablewebservices');
$protocols = get_config('core', 'webserviceprotocols');
$service = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
$function = $DB->get_record('external_functions', ['name' => 'local_ollamamcp_send_message']);
$token = $DB->get_record_sql("
    SELECT t.* FROM {external_tokens} t
    JOIN {external_services} s ON t.externalserviceid = s.id
    WHERE s.shortname = ? AND t.userid = ?
", ['ollamamcp', $USER->id]);

echo html_writer::tag('h4', 'Web Service Configuration');
echo html_writer::tag('p', 'Web services enabled: ' . ($ws_enabled ? html_writer::tag('span', '✅ Yes', ['style' => 'color: green;']) : html_writer::tag('span', '❌ No', ['style' => 'color: red;'])));
echo html_writer::tag('p', 'Available protocols: ' . htmlspecialchars($protocols));

echo html_writer::tag('h4', 'Ollama MCP Service');
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

echo html_writer::tag('h4', 'Quick Actions');
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link($PAGE->url . '?action=enable_webservices', html_writer::tag('button', 'Enable Web Services', ['class' => 'btn btn-primary w-100']));
echo html_writer::end_div();
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link($PAGE->url . '?action=register_function', html_writer::tag('button', 'Register Function', ['class' => 'btn btn-info w-100']));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link($PAGE->url . '?action=create_service', html_writer::tag('button', 'Create Service', ['class' => 'btn btn-warning w-100']));
echo html_writer::end_div();
echo html_writer::start_div('col-md-6 mb-2');
echo html_writer::link($PAGE->url . '?action=create_token', html_writer::tag('button', 'Create Token', ['class' => 'btn btn-success w-100']));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('hr', '');

echo html_writer::tag('h4', 'Complete Setup');
echo html_writer::tag('p', 'This will perform all the above steps in one operation:');
echo html_writer::tag('ul', 
    html_writer::tag('li', 'Enable web services and REST protocol') .
    html_writer::tag('li', 'Create Ollama MCP web service') .
    html_writer::tag('li', 'Register external function') .
    html_writer::tag('li', 'Create web service token for admin user') .
    html_writer::tag('li', 'Authorize admin user for the service')
);
echo html_writer::link($PAGE->url . '?action=complete_setup', html_writer::tag('button', 'Complete Setup (All Steps)', ['class' => 'btn btn-lg btn-success w-100 mt-3']));

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Test Section
echo html_writer::start_div('card mt-4');
echo html_writer::div('Test Web Service', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('h4', 'Test the AI Assistant');
echo html_writer::tag('p', 'Once the web service is set up, you can test the AI assistant:');
echo html_writer::link('/local/ollamamcp/', html_writer::tag('button', 'Open AI Assistant Chat', ['class' => 'btn btn-primary']));

echo html_writer::tag('h4', 'API Endpoint');
echo html_writer::tag('p', 'Web service endpoint:');
echo html_writer::tag('code', $CFG->wwwroot . '/webservice/rest/server.php?wsprotocol=rest&wstoken=' . ($token ? $token->token : 'YOUR_TOKEN'), ['class' => 'd-block p-2 bg-light']);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo $OUTPUT->footer();
?>
