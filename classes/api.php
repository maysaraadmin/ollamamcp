<?php
namespace local_ollamamcp;

defined('MOODLE_INTERNAL') || die();

class api {
    private $client;
    
    public function __construct() {
        $this->client = new \local_ollamamcp\mcp\client();
    }
    
    public function generate_course_summary($courseid, $model = null) {
        global $DB;
        
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        
        $prompt = "Summarize the following course information:\n";
        $prompt .= "Course Name: {$course->fullname}\n";
        $prompt .= "Short Name: {$course->shortname}\n";
        $prompt .= "Summary: {$course->summary}\n";
        $prompt .= "Please provide a concise summary suitable for students.";
        
        return $this->client->generate_completion($prompt, $model);
    }
    
    public function analyze_assignment_submission($assignmentid, $userid, $model = null) {
        global $DB;
        
        $assignment = $DB->get_record('assign', ['id' => $assignmentid], '*', MUST_EXIST);
        $submission = $DB->get_record('assign_submission', 
            ['assignment' => $assignmentid, 'userid' => $userid], '*', MUST_EXIST);
        
        $prompt = "Analyze this assignment submission:\n";
        $prompt .= "Assignment: {$assignment->name}\n";
        $prompt .= "Instructions: {$assignment->intro}\n";
        // Add more context as needed
        
        return $this->client->generate_completion($prompt, $model);
    }
    
    public function generate_quiz_questions($courseid, $topic, $num_questions = 5, $model = null) {
        $prompt = "Generate {$num_questions} quiz questions about '{$topic}' for a Moodle course.\n";
        $prompt .= "Format each question with:\n";
        $prompt .= "1. Question text\n";
        $prompt .= "2. Multiple choice options (A, B, C, D)\n";
        $prompt .= "3. Correct answer\n";
        $prompt .= "4. Explanation\n";
        
        return $this->client->generate_completion($prompt, $model);
    }
    
    public function provide_feedback_on_text($text, $context = 'general', $model = null) {
        $prompt = "Provide constructive feedback on the following text.\n";
        $prompt .= "Context: {$context}\n";
        $prompt .= "Text to analyze:\n{$text}\n";
        $prompt .= "Provide specific feedback on:\n";
        $prompt .= "1. Clarity and organization\n";
        $prompt .= "2. Grammar and spelling\n";
        $prompt .= "3. Content quality\n";
        $prompt .= "4. Suggestions for improvement";
        
        return $this->client->generate_completion($prompt, $model);
    }
    
    public function chat_with_context($messages, $model = null) {
        try {
            $response = $this->client->chat_completion($messages, $model);
            // Handle different response formats from Ollama
            if (isset($response['message']['content'])) {
                return ['response' => $response['message']['content']];
            } elseif (isset($response['response'])) {
                return ['response' => $response['response']];
            } else {
                return ['response' => 'Invalid response format from AI service'];
            }
        } catch (\Exception $e) {
            return ['response' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function get_available_models() {
        return $this->client->list_models();
    }
}