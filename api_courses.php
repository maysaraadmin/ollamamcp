<?php
// API endpoint for Moodle course data
require_once('../../config.php');
require_once($CFG->libdir . '/weblib.php');

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

try {
    // Get API client
    $api = new \local_ollamamcp\mcp\client();
    
    // Get request parameters
    $type = optional_param('type', 'courses', PARAM_ALPHA);
    $limit = optional_param('limit', 10, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    
    switch ($type) {
        case 'courses':
            $data = $api->get_moodle_courses($limit);
            break;
            
        case 'activities':
            $data = $api->get_moodle_activities($courseid > 0 ? $courseid : null);
            break;
            
        case 'users':
            $data = $api->get_moodle_users($courseid > 0 ? $courseid : null, $limit);
            break;
            
        case 'categories':
            $data = $api->get_moodle_categories();
            break;
            
        case 'user':
            $data = $api->get_moodle_info('user');
            break;
            
        case 'stats':
            $data = $api->get_moodle_info('stats');
            break;
            
        case 'validation':
            $data = $api->validate_moodle_installation();
            break;
            
        case 'platform':
            $data = $api->get_moodle_platform_info();
            break;
            
        case 'all':
            $data = $api->get_moodle_info('all');
            break;
            
        case 'general':
        default:
            $data = $api->get_moodle_info('general');
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
