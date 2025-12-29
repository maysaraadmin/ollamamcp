<?php
// Unified Moodle API endpoint for Ollama MCP - MOODLE DATA ONLY
require_once('../../config.php');
global $CFG, $DB;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// MOODLE-DATA-ONLY: Validate this is a legitimate Moodle request
if (!defined('MOODLE_INTERNAL') || empty($CFG->wwwroot) || empty($CFG->version)) {
    http_response_code(403);
    echo json_encode(['error' => 'Moodle environment validation failed']);
    exit;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = optional_param('type', 'platform', PARAM_ALPHA);
$limit = optional_param('limit', 10, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

try {
    // MOODLE-DATA-ONLY: All data comes from this Moodle installation only
    $data = [];
    
    switch ($type) {
        case 'platform':
            // MOODLE-DATA-ONLY: Return only this Moodle's platform information
            $data = [
                'platform_name' => 'Moodle LMS',
                'platform_url' => $CFG->wwwroot,
                'platform_version' => $CFG->version,
                'platform_release' => $CFG->release,
                'db_type' => $CFG->dbtype,
                'db_host' => $CFG->dbhost,
                'site_name' => $CFG->sitename ?: 'Moodle Site',
                'site_shortname' => $CFG->shortname ?: 'Moodle',
                'validation_hash' => md5($CFG->wwwroot . $CFG->version . $CFG->dbhost),
                'data_source' => 'MOODLE_DATABASE_ONLY'
            ];
            break;
            
        case 'courses':
            $courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname, shortname, summary', 0, $limit);
            $data = [];
            foreach ($courses as $course) {
                if ($course->id > 1) { // Skip site course
                    $data[] = [
                        'id' => $course->id,
                        'fullname' => $course->fullname,
                        'shortname' => $course->shortname,
                        'summary' => substr(strip_tags($course->summary), 0, 200)
                    ];
                }
            }
            break;
            
        case 'activities':
            if ($courseid > 0) {
                $activities = $DB->get_records('course_modules', ['course' => $courseid], 'id ASC', 'id, module, instance', 0, $limit);
                $data = [];
                foreach ($activities as $activity) {
                    $module = $DB->get_record('modules', ['id' => $activity->module]);
                    if ($module) {
                        $data[] = [
                            'id' => $activity->id,
                            'type' => $module->name,
                            'instance' => $activity->instance
                        ];
                    }
                }
            } else {
                $data = ['message' => 'Course ID required for activities'];
            }
            break;
            
        case 'users':
            if ($courseid > 0) {
                // Get enrolled users for specific course
                $context = context_course::instance($courseid);
                $users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email');
            } else {
                // Get all active users (limited)
                $users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0], 'id DESC', 'id, username, firstname, lastname, email', 0, $limit);
            }
            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'fullname' => trim($user->firstname . ' ' . $user->lastname),
                    'email' => $user->email
                ];
            }
            break;
            
        case 'categories':
            $categories = $DB->get_records('course_categories', ['visible' => 1], 'name ASC', 'id, name, description', 0, $limit);
            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => substr(strip_tags($category->description), 0, 200)
                ];
            }
            break;
            
        case 'stats':
            $data = [
                'total_courses' => $DB->count_records('course', ['visible' => 1]) - 1, // Exclude site course
                'total_users' => $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]),
                'total_categories' => $DB->count_records('course_categories', ['visible' => 1]),
                'moodle_version' => $CFG->version,
                'moodle_release' => $CFG->release
            ];
            break;
            
        case 'validation':
            $data = [
                'moodle_installed' => true,
                'database_connected' => $DB->get_dbfamily(),
                'config_loaded' => !empty($CFG->wwwroot),
                'api_working' => true
            ];
            break;
            
        default:
            $data = ['message' => 'Unknown type: ' . $type];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time(),
        'type' => $type,
        'moodle_only' => true,
        'data_source' => 'THIS_MOODLE_INSTALLATION_ONLY',
        'platform_url' => $CFG->wwwroot
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
