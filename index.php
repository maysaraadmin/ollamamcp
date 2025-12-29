<?php
// Redirect to MCP chat interface
require_once('../../config.php');

// Get courseid parameter
$courseid = optional_param('courseid', 0, PARAM_INT);

// Redirect to MCP chat
$url = new moodle_url('/local/ollamamcp/mcp_chat.php');
if ($courseid > 0) {
    $url->param('courseid', $courseid);
}

redirect($url);
?>
