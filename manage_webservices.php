<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

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

if ($action === 'enable_webservices') {
    set_config('enablewebservices', 1);
    set_config('webserviceprotocols', 'rest');
    redirect($PAGE->url, 'Web services enabled');
}

if ($action === 'register_function') {
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
    
    $existing = $DB->get_record('external_functions', ['name' => $function['name']]);
    if ($existing) {
        $function['id'] = $existing->id;
        $DB->update_record('external_functions', $function);
    } else {
        $DB->insert_record('external_functions', $function);
    }
    redirect($PAGE->url, 'External function registered');
}

if ($action === 'create_service') {
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
    $function = new stdClass();
    $function->externalserviceid = $webservice->id;
    $function->functionname = 'local_ollamamcp_send_message';
    
    $existing_func = $DB->get_record('external_services_functions', [
        'externalserviceid' => $webservice->id,
        'functionname' => 'local_ollamamcp_send_message'
    ]);
    
    if (!$existing_func) {
        $DB->insert_record('external_services_functions', $function);
    }
    
    redirect($PAGE->url, 'Web service created');
}

if ($action === 'authorize_user') {
    // Authorize admin user
    $admin_user = $DB->get_record('user', ['username' => 'admin']);
    if ($admin_user) {
        $service = $DB->get_record('external_services', ['shortname' => 'ollamamcp']);
        
        if ($service) {
            // Authorize user
            $auth = new stdClass();
            $auth->externalserviceid = $service->id;
            $auth->userid = $admin_user->id;
            
            $existing_auth = $DB->get_record('external_services_users', [
                'externalserviceid' => $service->id,
                'userid' => $admin_user->id
            ]);
            
            if (!$existing_auth) {
                $DB->insert_record('external_services_users', $auth);
            }
            
            // Create token
            $existing_token = $DB->get_record_sql("
                SELECT t.* FROM {external_tokens} t
                JOIN {external_services} s ON t.externalserviceid = s.id
                WHERE t.userid = ? AND s.shortname = ?
            ", [$admin_user->id, 'ollamamcp']);
            
            if (!$existing_token) {
                $token = new stdClass();
                $token->token = md5(uniqid('', true));
                $token->externalserviceid = $service->id;
                $token->userid = $admin_user->id;
                $token->contextid = $context->id;
                $token->creatorid = $admin_user->id;
                $token->timecreated = time();
                $token->tokentype = 0;
                
                $DB->insert_record('external_tokens', $token);
            }
        }
    }
    redirect($PAGE->url, 'User authorized');
}

// Current status
$ws_enabled = get_config('core', 'enablewebservices');
$protocols = get_config('core', 'webserviceprotocols');
$plugin_enabled = get_config('local_ollamamcp', 'enabled');
$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
$default_model = get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest';

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-12');

// Status Card
echo html_writer::start_div('card mb-3');
echo html_writer::div('Current Status', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_tag('table', ['class' => 'table table-bordered']);
echo html_writer::start_tag('tbody');

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Plugin Enabled') . 
    html_writer::tag('td', $plugin_enabled ? 
        html_writer::tag('span', '✅ Yes', ['class' => 'badge bg-success']) : 
        html_writer::tag('span', '❌ No', ['class' => 'badge bg-danger']))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Web Services Enabled') . 
    html_writer::tag('td', $ws_enabled ? 
        html_writer::tag('span', '✅ Yes', ['class' => 'badge bg-success']) : 
        html_writer::tag('span', '❌ No', ['class' => 'badge bg-danger']))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'REST Protocol') . 
    html_writer::tag('td', strpos($protocols, 'rest') !== false ? 
        html_writer::tag('span', '✅ Enabled', ['class' => 'badge bg-success']) : 
        html_writer::tag('span', '❌ Disabled', ['class' => 'badge bg-danger']))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'External Function') . 
    html_writer::tag('td', $DB->record_exists('external_functions', ['name' => 'local_ollamamcp_send_message']) ? 
        html_writer::tag('span', '✅ Registered', ['class' => 'badge bg-success']) : 
        html_writer::tag('span', '❌ Not registered', ['class' => 'badge bg-danger']))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Web Service') . 
    html_writer::tag('td', $DB->record_exists('external_services', ['shortname' => 'ollamamcp']) ? 
        html_writer::tag('span', '✅ Created', ['class' => 'badge bg-success']) : 
        html_writer::tag('span', '❌ Not created', ['class' => 'badge bg-danger']))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Ollama URL') . 
    html_writer::tag('td', html_writer::tag('code', $ollama_url))
);

echo html_writer::tag('tr', 
    html_writer::tag('td', 'Default Model') . 
    html_writer::tag('td', html_writer::tag('code', $default_model))
);

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Actions Card
echo html_writer::start_div('card mb-3');
echo html_writer::div('Setup Actions', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_div('d-grid gap-2');

if (!$ws_enabled) {
    echo html_writer::link($PAGE->url . '?action=enable_webservices', 
        html_writer::tag('button', 'Enable Web Services', ['class' => 'btn btn-primary']));
}

if (!$DB->record_exists('external_functions', ['name' => 'local_ollamamcp_send_message'])) {
    echo html_writer::link($PAGE->url . '?action=register_function', 
        html_writer::tag('button', 'Register External Function', ['class' => 'btn btn-warning']));
}

if (!$DB->record_exists('external_services', ['shortname' => 'ollamamcp'])) {
    echo html_writer::link($PAGE->url . '?action=create_service', 
        html_writer::tag('button', 'Create Web Service', ['class' => 'btn btn-info']));
}

echo html_writer::link($PAGE->url . '?action=authorize_user', 
    html_writer::tag('button', 'Authorize Admin User', ['class' => 'btn btn-secondary']));

echo html_writer::end_div(); // d-grid
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// MCP Web Services Section
echo html_writer::start_div('card mb-3');
echo html_writer::div('MCP Web Services', 'card-header');
echo html_writer::start_div('card-body');

// Get only MCP-related services
$mcp_services = $DB->get_records_select('external_services', 'shortname LIKE ?', ['%ollamamcp%'], 'name ASC');

if ($mcp_services) {
    echo html_writer::start_tag('table', ['class' => 'table table-striped']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr', 
        html_writer::tag('th', 'Service Name') .
        html_writer::tag('th', 'Short Name') .
        html_writer::tag('th', 'Enabled') .
        html_writer::tag('th', 'Functions') .
        html_writer::tag('th', 'Users')
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($mcp_services as $service) {
        $function_count = $DB->count_records('external_services_functions', ['externalserviceid' => $service->id]);
        $user_count = $DB->count_records('external_services_users', ['externalserviceid' => $service->id]);
        
        $enabled_badge = $service->enabled ? 
            html_writer::tag('span', '✅ Yes', ['class' => 'badge bg-success']) : 
            html_writer::tag('span', '❌ No', ['class' => 'badge bg-danger']);
        
        echo html_writer::tag('tr', 
            html_writer::tag('td', htmlspecialchars($service->name)) .
            html_writer::tag('td', html_writer::tag('code', htmlspecialchars($service->shortname))) .
            html_writer::tag('td', $enabled_badge) .
            html_writer::tag('td', $function_count) .
            html_writer::tag('td', $user_count)
        );
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', 'No MCP web services found.', ['class' => 'text-muted']);
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// MCP External Functions Section
echo html_writer::start_div('card mb-3');
echo html_writer::div('MCP External Functions', 'card-header');
echo html_writer::start_div('card-body');

// Get only MCP-related functions
$mcp_functions = $DB->get_records_select('external_functions', 'name LIKE ?', ['%ollamamcp%'], 'name ASC');

if ($mcp_functions) {
    echo html_writer::start_tag('table', ['class' => 'table table-striped']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr', 
        html_writer::tag('th', 'Function Name') .
        html_writer::tag('th', 'Class') .
        html_writer::tag('th', 'Method') .
        html_writer::tag('th', 'Type') .
        html_writer::tag('th', 'AJAX') .
        html_writer::tag('th', 'Description')
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($mcp_functions as $function) {
        $type_badge = $function->type === 'write' ? 
            html_writer::tag('span', 'Write', ['class' => 'badge bg-warning']) : 
            html_writer::tag('span', 'Read', ['class' => 'badge bg-info']);
        
        $ajax_badge = $function->ajax ? 
            html_writer::tag('span', '✅ Yes', ['class' => 'badge bg-success']) : 
            html_writer::tag('span', '❌ No', ['class' => 'badge bg-secondary']);
        
        echo html_writer::tag('tr', 
            html_writer::tag('td', html_writer::tag('code', htmlspecialchars($function->name))) .
            html_writer::tag('td', html_writer::tag('small', htmlspecialchars($function->classname))) .
            html_writer::tag('td', htmlspecialchars($function->methodname)) .
            html_writer::tag('td', $type_badge) .
            html_writer::tag('td', $ajax_badge) .
            html_writer::tag('td', htmlspecialchars($function->description))
        );
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', 'No MCP external functions found.', ['class' => 'text-muted']);
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// MCP Web Service Users Section
echo html_writer::start_div('card mb-3');
echo html_writer::div('MCP Web Service Users & Tokens', 'card-header');
echo html_writer::start_div('card-body');

// Get only MCP-related service users
$mcp_service_users = $DB->get_records_sql("
    SELECT u.username, u.email, s.name as service_name, s.shortname, t.token, t.timecreated
    FROM {external_services_users} su
    JOIN {user} u ON su.userid = u.id
    JOIN {external_services} s ON su.externalserviceid = s.id
    LEFT JOIN {external_tokens} t ON su.externalserviceid = t.externalserviceid AND su.userid = t.userid
    WHERE s.shortname LIKE ?
    ORDER BY s.name, u.username
", ['%ollamamcp%']);

if ($mcp_service_users) {
    echo html_writer::start_tag('table', ['class' => 'table table-striped']);
    echo html_writer::start_tag('thead');
    echo html_writer::tag('tr', 
        html_writer::tag('th', 'Username') .
        html_writer::tag('th', 'Email') .
        html_writer::tag('th', 'Service') .
        html_writer::tag('th', 'Token') .
        html_writer::tag('th', 'Created')
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($mcp_service_users as $user) {
        $token_display = $user->token ? 
            substr($user->token, 0, 8) . '...' : 
            html_writer::tag('span', 'No token', ['class' => 'text-muted']);
        
        echo html_writer::tag('tr', 
            html_writer::tag('td', htmlspecialchars($user->username)) .
            html_writer::tag('td', htmlspecialchars($user->email)) .
            html_writer::tag('td', htmlspecialchars($user->service_name)) .
            html_writer::tag('td', html_writer::tag('code', $token_display)) .
            html_writer::tag('td', userdate($user->timecreated))
        );
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo html_writer::tag('p', 'No MCP web service users found.', ['class' => 'text-muted']);
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Test Section
echo html_writer::start_div('card mb-3');
echo html_writer::div('Test Functions', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6');

echo html_writer::tag('h5', 'Test Ollama Connection');
echo html_writer::link($CFG->wwwroot . '/local/ollamamcp/test_ollama.php', 
    html_writer::tag('button', 'Test Ollama Server', ['class' => 'btn btn-outline-primary me-2']));

echo html_writer::end_div(); // col
echo html_writer::start_div('col-md-6');

echo html_writer::tag('h5', 'Test AI Assistant');
echo html_writer::link($CFG->wwwroot . '/local/ollamamcp/', 
    html_writer::tag('button', 'Open AI Chat', ['class' => 'btn btn-outline-success me-2']));

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
?>
