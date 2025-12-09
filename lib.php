<?php
defined('MOODLE_INTERNAL') || die();

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