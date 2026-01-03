<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Check if Ollama MCP plugin is enabled
 * @return bool
 */
function local_ollamamcp_is_enabled() {
    return get_config('local_ollamamcp', 'enabled') && 
           get_config('core', 'enablewebservices');
}

/**
 * Validate Ollama server URL
 * @param string $url
 * @return bool
 */
function local_ollamamcp_validate_server_url($url) {
    if (empty($url)) {
        return false;
    }
    
    // Check if URL is valid
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Check if it's a local or allowed URL
    $parsed = parse_url($url);
    $allowed_hosts = ['localhost', '127.0.0.1'];
    
    return in_array($parsed['host'], $allowed_hosts) || 
           strpos($parsed['host'], '.local') !== false;
}

/**
 * Log Ollama MCP errors
 * @param string $message
 * @param string $level
 */
function local_ollamamcp_log($message, $level = 'error') {
    debugging($message, DEBUG_DEVELOPER, "local_ollamamcp");
}

/**
 * Check user capability for Ollama MCP
 * @param context $context
 * @param string $capability
 * @return bool
 */
function local_ollamamcp_has_capability($capability, $context = null) {
    global $USER;
    
    if (!$context) {
        $context = context_system::instance();
    }
    
    return has_capability($capability, $context);
}

function local_ollamamcp_before_http_headers() {
    // Example hook: Add MCP capabilities to page
    if (get_config('local_ollamamcp', 'enabled')) {
        // Initialize MCP client if needed
    }
}

function local_ollamamcp_extend_navigation_course($navigation, $course, $context) {
    if (get_config('local_ollamamcp', 'enabled') && has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/local/ollamamcp/index.php', ['courseid' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_ollamamcp'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'ollamamcp',
            new pix_icon('i/settings', '')
        );
    }
}

function local_ollamamcp_get_footer_html() {
    global $PAGE;
    
    if (!get_config('local_ollamamcp', 'enabled')) {
        return '';
    }
    
    // Only add to pages where it makes sense
    if ($PAGE->pagetype === 'course-view' || 
        $PAGE->pagetype === 'mod-assign-view' ||
        $PAGE->pagetype === 'mod-quiz-attempt') {
        
        $html = '<div id="ollamamcp-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">';
        $html .= '<button onclick="local_ollamamcp_toggle_chat()" class="btn btn-primary">';
        $html .= '<i class="fa fa-robot"></i> AI Assistant</button>';
        $html .= '</div>';
        $html .= '<script>
            function local_ollamamcp_toggle_chat() {
                // Implement chat interface
                console.log("Ollama MCP chat toggled");
            }
        </script>';
        
        return $html;
    }
    
    return '';
}

/**
 * Handle web service setup when plugin settings are saved
 * @param bool $enable Whether to enable web services
 * @return bool Success status
 */
function local_ollamamcp_setup_webservice($enable = true) {
    global $DB, $CFG;
    
    if (!$enable) {
        return true;
    }
    
    try {
        // Enable web services and REST protocol
        set_config('enablewebservices', 1);
        set_config('webserviceprotocols', 'rest');
        
        // Get configuration values
        $service_name = get_config('local_ollamamcp', 'webservicename') ?: 'Ollama MCP Service';
        $service_shortname = get_config('local_ollamamcp', 'webserviceshortname') ?: 'ollamamcp';
        
        // Create web service
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
        
        // Create token and authorize admin user if requested
        if (get_config('local_ollamamcp', 'createtokenforadmin')) {
            $admin_user = $DB->get_record('user', ['username' => 'admin']);
            if ($admin_user) {
                $context = context_system::instance();
                
                $existing_token = $DB->get_record_sql("
                    SELECT t.* FROM {external_tokens} t
                    JOIN {external_services} s ON t.externalserviceid = s.id
                    WHERE t.userid = ? AND s.shortname = ?
                ", [$admin_user->id, $service_shortname]);
                
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
        }
        
        return true;
        
    } catch (Exception $e) {
        local_ollamamcp_log('Web service setup failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get web service token information
 * @return array|null Token information or null if not found
 */
function local_ollamamcp_get_token_info() {
    global $DB;
    
    $service_shortname = get_config('local_ollamamcp', 'webserviceshortname') ?: 'ollamamcp';
    
    // Get admin user
    $admin_user = $DB->get_record('user', ['username' => 'admin']);
    if (!$admin_user) {
        return null;
    }
    
    // Get token for admin user and our service
    $token = $DB->get_record_sql("
        SELECT t.*, s.name as servicename 
        FROM {external_tokens} t
        JOIN {external_services} s ON t.externalserviceid = s.id
        WHERE t.userid = ? AND s.shortname = ?
    ", [$admin_user->id, $service_shortname]);
    
    if (!$token) {
        return null;
    }
    
    return [
        'id' => $token->id,
        'token' => $token->token,
        'servicename' => $token->servicename,
        'created' => date('Y-m-d H:i:s', $token->timecreated),
        'userid' => $token->userid
    ];
}

/**
 * Regenerate web service token
 * @return bool Success status
 */
function local_ollamamcp_regenerate_token() {
    global $DB;
    
    $service_shortname = get_config('local_ollamamcp', 'webserviceshortname') ?: 'ollamamcp';
    
    // Get admin user
    $admin_user = $DB->get_record('user', ['username' => 'admin']);
    if (!$admin_user) {
        return false;
    }
    
    // Get the web service
    $webservice = $DB->get_record('external_services', ['shortname' => $service_shortname]);
    if (!$webservice) {
        return false;
    }
    
    $context = context_system::instance();
    
    // Delete existing token
    $existing_token = $DB->get_record_sql("
        SELECT t.* FROM {external_tokens} t
        JOIN {external_services} s ON t.externalserviceid = s.id
        WHERE t.userid = ? AND s.shortname = ?
    ", [$admin_user->id, $service_shortname]);
    
    if ($existing_token) {
        $DB->delete_records('external_tokens', ['id' => $existing_token->id]);
    }
    
    // Create new token
    $token = new stdClass();
    $token->token = md5(uniqid('', true));
    $token->externalserviceid = $webservice->id;
    $token->userid = $admin_user->id;
    $token->contextid = $context->id;
    $token->creatorid = $admin_user->id;
    $token->timecreated = time();
    $token->tokentype = 0;
    
    $DB->insert_record('external_tokens', $token);
    
    return true;
}