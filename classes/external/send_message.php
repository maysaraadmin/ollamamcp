<?php
namespace local_ollamamcp\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
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
            'sources' => new \external_value(PARAM_TEXT, 'Documentation sources used', VALUE_OPTIONAL),
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
            throw new \Exception('Plugin is disabled');
        }
        
        try {
            // Use API class with generate endpoint (faster)
            $api = new \local_ollamamcp\mcp\client();
            
            // Enhanced prompt with documentation context if enabled
            $enhanced_message = $message;
            $doc_results = ['devdocs' => [], 'phpdocs' => []];
            
            if (get_config('local_ollamamcp', 'enable_docsearch')) {
                // Search documentation for relevant context
                $doc_search = new \local_ollamamcp\mcp\documentation_search();
                $search_limit = get_config('local_ollamamcp', 'docsearch_limit') ?: 3;
                $doc_results = $doc_search->search_documentation($message, ['limit' => $search_limit]);
                $doc_context = $doc_search->format_results_for_ai($doc_results);
                
                if (!empty(trim($doc_context))) {
                    $enhanced_message = "Context from Moodle documentation:\n" . $doc_context . "\n\nUser question: " . $message;
                }
            }
            
            $response = $api->generate_completion($enhanced_message);
            
            return [
                'response' => $response['response'] ?? 'No response from AI assistant.',
                'sources' => $this->format_sources($doc_results)
            ];
            
        } catch (\Exception $e) {
            // Log actual error for debugging
            debugging('AI Assistant Error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            // Return user-friendly error message
            return [
                'response' => 'Sorry, I could not process your request. Please try again.',
            ];
        }
    }
    
    /**
     * Format documentation sources for response
     * @param array $doc_results Documentation search results
     * @return string Formatted sources
     */
    private static function format_sources($doc_results) {
        $sources = [];
        
        foreach ($doc_results['devdocs'] as $result) {
            $sources[] = "[DEVDOCS] {$result['title']} ({$result['file']})";
        }
        
        foreach ($doc_results['phpdocs'] as $result) {
            $sources[] = "[PHPDOCS] {$result['title']} ({$result['file']})";
        }
        
        return empty($sources) ? '' : 'Sources: ' . implode(', ', $sources);
    }
}
