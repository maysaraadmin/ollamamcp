<?php
namespace local_ollamamcp;

defined('MOODLE_INTERNAL') || die();

/**
 * Chat storage class for saving conversations to Moodle database
 */
class chat_storage {
    
    /**
     * Save a chat message to the database
     * 
     * @param int $userid User ID
     * @param int $courseid Course ID (0 for system context)
     * @param string $session_id Chat session identifier
     * @param string $message_type Message type: 'user' or 'assistant'
     * @param string $message Message content
     * @param array $context_data Moodle context data used
     * @param string $model_used Ollama model used
     * @param int $response_time Response time in milliseconds
     * @param int $tokens_used Number of tokens used
     * @return int|false Message ID or false on failure
     */
    public static function save_message($userid, $courseid, $session_id, $message_type, $message, 
                                     $context_data = null, $model_used = null, $response_time = 0, $tokens_used = 0) {
        global $DB;
        
        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->session_id = $session_id;
        $record->message_type = $message_type;
        $record->message = $message;
        $record->context_data = $context_data ? json_encode($context_data) : null;
        $record->model_used = $model_used;
        $record->response_time = $response_time;
        $record->tokens_used = $tokens_used;
        $record->timecreated = time();
        $record->timemodified = time();
        
        try {
            return $DB->insert_record('local_ollamamcp_chats', $record);
        } catch (\Exception $e) {
            debugging('Error saving chat message: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
    
    /**
     * Get chat history for a user
     * 
     * @param int $userid User ID
     * @param int $courseid Course ID (0 for system context)
     * @param string $session_id Optional session ID to filter by
     * @param int $limit Maximum number of messages to retrieve
     * @return array Array of chat messages
     */
    public static function get_chat_history($userid, $courseid = 0, $session_id = null, $limit = 50) {
        global $DB;
        
        $conditions = ['userid' => $userid, 'courseid' => $courseid];
        if ($session_id) {
            $conditions['session_id'] = $session_id;
        }
        
        try {
            $messages = $DB->get_records('local_ollamamcp_chats', $conditions, 
                                       'timecreated ASC', '*', 0, $limit);
            
            $chat_history = [];
            foreach ($messages as $message) {
                $chat_history[] = [
                    'id' => $message->id,
                    'type' => $message->message_type,
                    'message' => $message->message,
                    'context_data' => $message->context_data ? json_decode($message->context_data, true) : null,
                    'model_used' => $message->model_used,
                    'response_time' => $message->response_time,
                    'tokens_used' => $message->tokens_used,
                    'timecreated' => $message->timecreated,
                    'formatted_time' => date('H:i', $message->timecreated)
                ];
            }
            
            return $chat_history;
        } catch (\Exception $e) {
            debugging('Error retrieving chat history: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Get chat sessions for a user
     * 
     * @param int $userid User ID
     * @param int $courseid Course ID (0 for system context)
     * @return array Array of session information
     */
    public static function get_user_sessions($userid, $courseid = 0) {
        global $DB;
        
        $sql = "SELECT session_id, MIN(timecreated) as start_time, MAX(timecreated) as end_time, COUNT(*) as message_count
                FROM {local_ollamamcp_chats}
                WHERE userid = :userid AND courseid = :courseid
                GROUP BY session_id
                ORDER BY start_time DESC";
        
        try {
            return $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        } catch (\Exception $e) {
            debugging('Error retrieving chat sessions: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }
    }
    
    /**
     * Clear chat history for a user
     * 
     * @param int $userid User ID
     * @param int $courseid Course ID (0 for system context)
     * @param string $session_id Optional session ID to clear specific session
     * @return bool Success status
     */
    public static function clear_chat_history($userid, $courseid = 0, $session_id = null) {
        global $DB;
        
        $conditions = ['userid' => $userid, 'courseid' => $courseid];
        if ($session_id) {
            $conditions['session_id'] = $session_id;
        }
        
        try {
            return $DB->delete_records('local_ollamamcp_chats', $conditions);
        } catch (\Exception $e) {
            debugging('Error clearing chat history: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
    
    /**
     * Get chat statistics
     * 
     * @param int $userid Optional user ID for user-specific stats
     * @param int $courseid Optional course ID for course-specific stats
     * @return array Statistics data
     */
    public static function get_chat_statistics($userid = null, $courseid = null) {
        global $DB;
        
        $sql = "SELECT 
                    COUNT(*) as total_messages,
                    COUNT(DISTINCT userid) as unique_users,
                    COUNT(DISTINCT session_id) as total_sessions,
                    AVG(response_time) as avg_response_time,
                    SUM(tokens_used) as total_tokens,
                    COUNT(CASE WHEN message_type = 'user' THEN 1 END) as user_messages,
                    COUNT(CASE WHEN message_type = 'assistant' THEN 1 END) as assistant_messages
                FROM {local_ollamamcp_chats}
                WHERE 1=1";
        
        $params = [];
        if ($userid) {
            $sql .= " AND userid = :userid";
            $params['userid'] = $userid;
        }
        if ($courseid) {
            $sql .= " AND courseid = :courseid";
            $params['courseid'] = $courseid;
        }
        
        try {
            return $DB->get_record_sql($sql, $params);
        } catch (\Exception $e) {
            debugging('Error retrieving chat statistics: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }
    
    /**
     * Generate a unique session ID
     * 
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return string Session ID
     */
    public static function generate_session_id($userid, $courseid) {
        return 'chat_' . $userid . '_' . $courseid . '_' . date('Y-m-d_H-i-s') . '_' . uniqid();
    }
}
