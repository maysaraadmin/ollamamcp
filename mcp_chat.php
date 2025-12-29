<?php
require_once('../../config.php');

// Get courseid parameter - make it optional to allow direct access
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid > 0) {
    // Course context requested
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($courseid);
    require_capability('moodle/course:update', $context);
    
    $PAGE->set_url('/local/ollamamcp/mcp_chat.php', ['courseid' => $courseid]);
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('pluginname', 'local_ollamamcp'));
    $PAGE->set_heading($course->fullname);
} else {
    // System context - no specific course
    require_login();
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    
    $PAGE->set_url('/local/ollamamcp/mcp_chat.php');
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('pluginname', 'local_ollamamcp'));
    $PAGE->set_heading(get_string('pluginname', 'local_ollamamcp'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Ollama AI Assistant Chat');

// Ollama Server status
echo $OUTPUT->heading('Ollama Server Status', 3);

$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';

echo html_writer::start_div('row mb-3');
echo html_writer::start_div('col-md-12');
echo html_writer::start_div('card');
echo html_writer::div('Ollama Server Connection', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('p', 'The chat interface connects directly to the Ollama server for AI responses.');
echo html_writer::tag('p', 'Make sure Ollama is running on ' . html_writer::tag('code', $ollama_url) . ' before using the chat.');

// Ollama server info
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('h5', 'Ollama Server Information:');
echo html_writer::tag('p', 'Server URL: ' . html_writer::tag('code', $ollama_url));
echo html_writer::tag('p', 'Default Model: ' . html_writer::tag('code', get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest'));
echo html_writer::tag('small', 'The plugin connects directly to Ollama REST API');
echo html_writer::end_div();

echo html_writer::start_div('d-flex gap-2');
echo html_writer::link('javascript:void(0)', html_writer::tag('button', 'Check Ollama Server', ['class' => 'btn btn-primary', 'onclick' => 'checkOllamaServer()']));
echo html_writer::end_div();

echo html_writer::div('', 'alert alert-info mt-3', ['id' => 'ollama-status', 'style' => 'display:none;']);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

// JavaScript for Ollama server status check
$PAGE->requires->js_init_code("
    function checkOllamaServer() {
        var statusDiv = document.getElementById('ollama-status');
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-info mt-3';
        statusDiv.innerHTML = '<div class=\"spinner-border spinner-border-sm me-2\" role=\"status\"></div>Checking Ollama server...';
        
        var ollamaUrl = '" . $ollama_url . "';
        
        // Check Ollama API /api/tags endpoint
        fetch(ollamaUrl + '/api/tags', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response) {
            if (response.ok) {
                statusDiv.className = 'alert alert-success mt-3';
                statusDiv.innerHTML = '✅ <strong>Ollama Server is running</strong><br><small>Connected to ' + ollamaUrl + ' and API is responding.</small>';
            } else {
                throw new Error('HTTP ' + response.status);
            }
        }).catch(function(error) {
            statusDiv.className = 'alert alert-warning mt-3';
            statusDiv.innerHTML = '⚠️ <strong>Ollama Server is not running</strong><br><small>Could not connect to ' + ollamaUrl + '<br>Please start Ollama server: <code>ollama serve</code></small>';
        });
    }
    
    // Auto-check status when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkOllamaServer, 1000);
    });
");

echo html_writer::tag('hr', '');

// Display Ollama AI assistant interface
echo html_writer::start_div('ollama-chat-section');
echo html_writer::tag('h4', 'Ollama AI Assistant Chat');
echo html_writer::start_div('chat-container', ['style' => 'border: 1px solid #ddd; border-radius: 8px; height: 500px; display: flex; flex-direction: column; font-family: Arial, sans-serif; margin-bottom: 20px;']);

// Chat messages area
echo html_writer::start_div('chat-messages', ['id' => 'chat-messages', 'style' => 'flex: 1; padding: 15px; overflow-y: auto; background: #fff; border-bottom: 1px solid #ddd;']);
echo html_writer::start_div('message assistant', ['style' => 'margin-bottom: 15px; display: flex;']);
echo html_writer::start_div('message-content', ['style' => 'max-width: 70%; padding: 10px 15px; border-radius: 15px; background: #e9ecef;']);
echo html_writer::tag('div', 'Hello! I\'m your Ollama AI assistant. I connect directly to the Ollama server for AI-powered responses.');
echo html_writer::end_div(); // message-content
echo html_writer::end_div(); // message
echo html_writer::end_div(); // chat-messages

// Chat input area
echo html_writer::start_div('chat-input', ['style' => 'padding: 15px; display: flex; gap: 10px;']);
echo html_writer::tag('textarea', '', [
    'id' => 'chat-input',
    'placeholder' => 'Type your message here...',
    'rows' => '3',
    'style' => 'flex: 1; border: 1px solid #ddd; border-radius: 5px; padding: 10px; resize: none;'
]);
echo html_writer::tag('button', 'Send', [
    'id' => 'send-message',
    'class' => 'btn btn-primary',
    'onclick' => 'sendOllamaMessage()',
    'style' => 'align-self: flex-end; padding: 10px 20px;'
]);
echo html_writer::end_div(); // chat-input
echo html_writer::end_div(); // chat-container
echo html_writer::end_div(); // ollama-chat-section

// Ollama JavaScript for direct API communication
echo html_writer::start_tag('script');
echo "
var ollamaUrl = '" . $ollama_url . "';

function sendOllamaMessage() {
    var input = document.getElementById('chat-input');
    var message = input.value.trim();
    
    if (!message) return;
    
    console.log('Sending Ollama message:', message);
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    addMessage('...', 'assistant typing');
    
    // Send to Ollama API
    sendOllamaRequest(message);
}

function sendOllamaRequest(message) {
    var model = '" . (get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest') . "';
    
    var requestData = {
        model: model,
        prompt: message,
        stream: false,
        options: {
            temperature: 0.7,
            top_p: 0.9,
            num_predict: 500
        }
    };
    
    console.log('Ollama Request:', requestData);
    
    // Send to Ollama API directly
    fetch(ollamaUrl + '/api/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(function(response) {
        console.log('Ollama Response status:', response.status);
        return response.json();
    })
    .then(function(data) {
        console.log('Ollama Response data:', data);
        
        // Remove typing indicator
        var typingMsg = document.querySelector('.message.typing');
        if (typingMsg) typingMsg.remove();
        
        if (data.response) {
            addMessage(data.response, 'assistant');
        } else if (data.error) {
            var errorMsg = 'Ollama Error: ' + data.error;
            addMessage(errorMsg, 'assistant');
            console.log('Full error details:', data.error);
        } else {
            addMessage('Received Ollama response but format was unexpected', 'assistant');
            console.log('Unexpected response format:', data);
        }
    })
    .catch(function(error) {
        console.log('Ollama Error:', error);
        
        // Remove typing indicator
        var typingMsg = document.querySelector('.message.typing');
        if (typingMsg) typingMsg.remove();
        
        addMessage('Ollama Connection Error: ' + error.message, 'assistant');
    });
}

function addMessage(content, type) {
    var messagesContainer = document.getElementById('chat-messages');
    var messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + type;
    messageDiv.style.cssText = 'margin-bottom: 15px; display: flex; ' + (type === 'user' ? 'justify-content: flex-end;' : '');
    
    var contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    contentDiv.style.cssText = 'max-width: 70%; padding: 10px 15px; border-radius: 15px; ' + 
        (type === 'user' ? 'background: #007bff; color: white;' : 'background: #e9ecef;');
    contentDiv.textContent = content;
    
    messageDiv.appendChild(contentDiv);
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Handle Enter key in textarea
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('chat-input');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendOllamaMessage();
            }
        });
    }
});
";
echo html_writer::end_tag('script');

echo $OUTPUT->footer();
?>
