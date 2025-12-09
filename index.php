<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get courseid parameter - make it optional to allow direct access
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid > 0) {
    // Course context requested
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($courseid);
    require_capability('moodle/course:update', $context);
    
    $PAGE->set_url('/local/ollamamcp/index.php', ['courseid' => $courseid]);
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('pluginname', 'local_ollamamcp'));
    $PAGE->set_heading($course->fullname);
} else {
    // System context - no specific course
    require_login();
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    
    $PAGE->set_url('/local/ollamamcp/index.php');
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('pluginname', 'local_ollamamcp'));
    $PAGE->set_heading(get_string('pluginname', 'local_ollamamcp'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_ollamamcp'));

// Debug info
echo html_writer::div('Course ID: ' . $courseid . ', Context: ' . ($courseid > 0 ? 'course' : 'system'), 'alert alert-info');

// Display AI assistant interface
echo html_writer::div('', 'ollamamcp-container', ['id' => 'ollamamcp-container']);

// Include JavaScript
$PAGE->requires->js_call_amd('local_ollamamcp/chat', 'init', [
    'courseid' => $courseid,
    'wwwroot' => $CFG->wwwroot,
    'context' => $courseid > 0 ? 'course' : 'system'
]);

echo $OUTPUT->footer();
