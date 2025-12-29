<?php
// Chat API for storing and retrieving conversations
require_once('../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if database table exists
global $DB;
$table_exists = $DB->get_manager()->table_exists('local_ollamamcp_chats');

if (!$table_exists) {
    // Try to create the table automatically
    try {
        $sql = "CREATE TABLE IF NOT EXISTS {local_ollamamcp_chats} (
            id BIGINT NOT NULL AUTO_INCREMENT,
            userid BIGINT NOT NULL,
            courseid BIGINT NOT NULL DEFAULT 0,
            session_id VARCHAR(255) NOT NULL,
            message_type VARCHAR(10) NOT NULL,
            message LONGTEXT NOT NULL,
            context_data MEDIUMTEXT,
            model_used VARCHAR(100),
            response_time BIGINT DEFAULT 0,
            tokens_used BIGINT DEFAULT 0,
            timecreated BIGINT NOT NULL,
            timemodified BIGINT NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_timecreated (timecreated),
            INDEX idx_userid_course (userid, courseid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $DB->execute($sql);
        $table_exists = true;
        
        // Log successful table creation
        error_log('Chat table created automatically in local_ollamamcp');
        
    } catch (Exception $e) {
        // If table creation fails, return error but allow chat to continue
        echo json_encode([
            'success' => false,
            'error' => 'Database table does not exist and could not be created automatically.',
            'debug' => 'Table local_ollamamcp_chats not found. Error: ' . $e->getMessage(),
            'action_required' => 'Please run create_chat_table.php to create the database table.'
        ]);
        exit;
    }
}

require_login();

$action = optional_param('action', 'get', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$session_id = optional_param('session_id', '', PARAM_TEXT);

try {
    global $USER;
    
    switch ($action) {
        case 'save':
            // Save a chat message
            $message_type = required_param('message_type', PARAM_ALPHA);
            $message = required_param('message', PARAM_TEXT);
            $context_data = optional_param('context_data', '', PARAM_RAW);
            $model_used = optional_param('model_used', '', PARAM_TEXT);
            $response_time = optional_param('response_time', 0, PARAM_INT);
            $tokens_used = optional_param('tokens_used', 0, PARAM_INT);
            
            // Generate session ID if not provided
            if (empty($session_id)) {
                $session_id = \local_ollamamcp\chat_storage::generate_session_id($USER->id, $courseid);
            }
            
            $context_data_array = $context_data ? json_decode($context_data, true) : null;
            
            $message_id = \local_ollamamcp\chat_storage::save_message(
                $USER->id, 
                $courseid, 
                $session_id, 
                $message_type, 
                $message, 
                $context_data_array, 
                $model_used, 
                $response_time, 
                $tokens_used
            );
            
            if ($message_id) {
                echo json_encode([
                    'success' => true,
                    'message_id' => $message_id,
                    'session_id' => $session_id,
                    'timestamp' => time()
                ]);
            } else {
                throw new \Exception('Failed to save message');
            }
            break;
            
        case 'get':
            // Get chat history
            $limit = optional_param('limit', 50, PARAM_INT);
            
            $history = \local_ollamamcp\chat_storage::get_chat_history(
                $USER->id, 
                $courseid, 
                $session_id, 
                $limit
            );
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'session_id' => $session_id,
                'timestamp' => time()
            ]);
            break;
            
        case 'sessions':
            // Get user sessions
            $sessions = \local_ollamamcp\chat_storage::get_user_sessions($USER->id, $courseid);
            
            echo json_encode([
                'success' => true,
                'sessions' => array_values($sessions),
                'timestamp' => time()
            ]);
            break;
            
        case 'clear':
            // Clear chat history
            if (empty($session_id)) {
                throw new \Exception('Session ID required for clearing chat');
            }
            
            $cleared = \local_ollamamcp\chat_storage::clear_chat_history($USER->id, $courseid, $session_id);
            
            echo json_encode([
                'success' => $cleared,
                'message' => $cleared ? 'Chat history cleared' : 'Failed to clear chat history',
                'timestamp' => time()
            ]);
            break;
            
        case 'stats':
            // Get chat statistics
            $stats = \local_ollamamcp\chat_storage::get_chat_statistics($USER->id, $courseid);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats,
                'timestamp' => time()
            ]);
            break;
            
        default:
            throw new \Exception('Unknown action: ' . $action);
    }
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
