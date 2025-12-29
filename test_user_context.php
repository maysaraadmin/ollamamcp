<?php
// Test user context in AI responses
require_once('../../config.php');

echo "<h1>🧪 User Context Test</h1>";

// Get current user info
global $USER, $CFG;
echo "<h2>Current Moodle User</h2>";
if (isset($USER->id) && $USER->id > 0) {
    echo "<p style='color: green;'>✅ Logged in user detected</p>";
    echo "<ul>";
    echo "<li>ID: {$USER->id}</li>";
    echo "<li>Username: " . htmlspecialchars($USER->username) . "</li>";
    echo "<li>Email: " . htmlspecialchars($USER->email) . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠️ No logged-in user or guest user</p>";
    echo "<p>User ID: " . ($USER->id ?? 'not set') . "</p>";
}

// Test AI response with user context
echo "<h2>🤖 AI Response Test</h2>";

$test_prompts = [
    'Who is the current user?',
    'What is my username?',
    'Tell me about the current user',
    'What user information do you have?'
];

foreach ($test_prompts as $prompt) {
    echo "<h3>Testing: " . htmlspecialchars($prompt) . "</h3>";
    
    try {
        $api = new \local_ollamamcp\api();
        $result = $api->generate_completion($prompt);
        
        if (isset($result['response'])) {
            $response = $result['response'];
            echo "<p><strong>AI Response:</strong> " . htmlspecialchars($response) . "</p>";
            
            // Check if response contains user info
            if (isset($USER->id) && $USER->id > 0) {
                if (strpos($response, $USER->username) !== false || strpos($response, $USER->email) !== false) {
                    echo "<p style='color: green;'>✅ Response contains user information</p>";
                } else {
                    echo "<p style='color: red;'>❌ Response does not contain user information</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ No response from AI</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

// Test the enhanced prompt directly
echo "<h2>🔧 Enhanced Prompt Test</h2>";

$user_context = '';
if (isset($USER->id) && $USER->id > 0) {
    $user_context = "Current Moodle User: ID={$USER->id}, Username={$USER->username}, Email={$USER->email}. ";
}

$enhanced_prompt = $user_context . "You are an AI assistant integrated with Moodle LMS. " .
                  "When users ask about identity or current user, provide the Moodle user information above. " .
                  "Be helpful and concise. User message: Who is the current user?";

echo "<p><strong>Enhanced Prompt:</strong></p>";
echo "<pre>" . htmlspecialchars($enhanced_prompt) . "</pre>";

?>
