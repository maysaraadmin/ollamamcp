<?php
namespace local_ollamamcp\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');

class send_message extends \external_api {
    
    public static function execute_parameters() {
        return new \external_function_parameters([
            'message' => new \external_value(PARAM_TEXT, 'Message to send'),
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }
    
    public static function execute_returns() {
        return new \external_single_structure([
            'response' => new \external_value(PARAM_TEXT, 'AI response'),
        ]);
    }
    
    public static function execute($message, $courseid) {
        global $DB, $USER;
        
        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'message' => $message,
            'courseid' => $courseid,
        ]);
        
        // Handle system context (courseid = 0)
        if ($courseid == 0) {
            $context = \context_system::instance();
            self::validate_context($context);
            require_capability('moodle/site:config', $context);
        } else {
            // Validate course context
            $context = \context_course::instance($courseid);
            self::validate_context($context);
            require_capability('moodle/course:update', $context);
        }
        
        // Check if plugin is enabled
        if (!get_config('local_ollamamcp', 'enabled')) {
            throw new \moodle_exception('plugindisabled', 'local_ollamamcp');
        }
        
        try {
            // Use the API class to get response
            $api = new \local_ollamamcp\api();
            $response = $api->chat_with_context([
                ['role' => 'user', 'content' => $message]
            ]);
            
            return [
                'response' => $response['response'] ?? 'Sorry, I could not process your request.',
            ];
            
        } catch (\Exception $e) {
            // Return a user-friendly error message
            return [
                'response' => 'AI service is not configured. Please contact your administrator.',
            ];
        }
    }
}
