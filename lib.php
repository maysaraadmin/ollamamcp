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