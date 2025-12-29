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

// MCP Server controls
echo $OUTPUT->heading('MCP Server', 3);

$server_url = $CFG->wwwroot . '/local/ollamamcp/cli/start_mcp_server.php';
$server_status_url = $CFG->wwwroot . '/local/ollamamcp/classes/mcp/server.php';

echo html_writer::start_div('row mb-3');
echo html_writer::start_div('col-md-12');
echo html_writer::start_div('card');
echo html_writer::div('MCP Server Management', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('p', 'The MCP server should be started via command line. Use the button below to check if it\'s running.');

// Command line instructions
echo html_writer::start_div('alert alert-secondary');
echo html_writer::tag('h5', 'Command Line Instructions:');
echo html_writer::tag('code', 'cd c:\wamp64\www\robot\local\ollamamcp') . html_writer::empty_tag('br');
echo html_writer::tag('code', 'C:\wamp64\bin\php\php8.3.14\php.exe cli/start_mcp_server.php') . html_writer::empty_tag('br');
echo html_writer::tag('small', 'Or with custom options: php cli/start_mcp_server.php --host=localhost --port=8080 --model=llama3.2:latest');
echo html_writer::end_div();

echo html_writer::start_div('d-flex gap-2');
echo html_writer::link('javascript:void(0)', html_writer::tag('button', 'Check Server Status', ['class' => 'btn btn-primary', 'onclick' => 'checkServerStatus()']));
echo html_writer::end_div();

echo html_writer::div('', 'alert alert-info mt-3', ['id' => 'server-status', 'style' => 'display:none;']);

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

// JavaScript for server status check
$PAGE->requires->js_init_code("
    function checkServerStatus() {
        var statusDiv = document.getElementById('server-status');
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-info mt-3';
        statusDiv.innerHTML = '<div class=\"spinner-border spinner-border-sm me-2\" role=\"status\"></div>Checking server status...';
        
        // Check multiple ports and provide detailed feedback
        var ports = [8080, 8081, 8082];
        var checkedPorts = 0;
        var serverFound = false;
        
        function checkPort(port) {
            // Use fetch with timeout for more reliable detection
            var controller = new AbortController();
            var timeoutId = setTimeout(function() {
                controller.abort();
            }, 3000);
            
            fetch('http://127.0.0.1:' + port, {
                method: 'HEAD',
                mode: 'no-cors',
                signal: controller.signal
            }).then(function(response) {
                if (!serverFound) {
                    serverFound = true;
                    statusDiv.className = 'alert alert-success mt-3';
                    statusDiv.innerHTML = '✅ <strong>MCP Server is running on port ' + port + '</strong><br><small>Server is ready to handle AI assistant requests.</small>';
                }
                checkedPorts++;
                if (checkedPorts === ports.length && !serverFound) {
                    statusDiv.className = 'alert alert-warning mt-3';
                    statusDiv.innerHTML = '⚠️ <strong>MCP Server is not running</strong><br><small>Please start the server using the command line instructions above.</small>';
                }
            }).catch(function(error) {
                checkedPorts++;
                if (checkedPorts === ports.length && !serverFound) {
                    statusDiv.className = 'alert alert-warning mt-3';
                    statusDiv.innerHTML = '⚠️ <strong>MCP Server is not running</strong><br><small>Please start the server using the command line instructions above.</small>';
                }
            }).finally(function() {
                clearTimeout(timeoutId);
            });
        }
        
        // Check all ports
        ports.forEach(checkPort);
        
        // Auto-refresh status every 30 seconds
        setTimeout(function() {
            if (statusDiv.style.display !== 'none') {
                checkServerStatus();
            }
        }, 30000);
    }
    
    // Auto-check status when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkServerStatus, 1000);
    });
");

echo html_writer::tag('hr', '');

// Display AI assistant interface
echo html_writer::start_div('ollamamcp-chat-section');
echo html_writer::tag('h4', 'AI Assistant Chat');
echo html_writer::start_div('chat-container', ['style' => 'border: 1px solid #ddd; border-radius: 8px; height: 500px; display: flex; flex-direction: column; font-family: Arial, sans-serif; margin-bottom: 20px;']);

// Chat messages area
echo html_writer::start_div('chat-messages', ['id' => 'chat-messages', 'style' => 'flex: 1; padding: 15px; overflow-y: auto; background: #fff; border-bottom: 1px solid #ddd;']);
echo html_writer::start_div('message assistant', ['style' => 'margin-bottom: 15px; display: flex;']);
echo html_writer::start_div('message-content', ['style' => 'max-width: 70%; padding: 10px 15px; border-radius: 15px; background: #e9ecef;']);
echo html_writer::tag('div', 'Hello! I\'m your AI assistant. How can I help you today?');
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
    'onclick' => 'sendMessage()',
    'style' => 'align-self: flex-end; padding: 10px 20px;'
]);
echo html_writer::end_div(); // chat-input
echo html_writer::end_div(); // chat-container
echo html_writer::end_div(); // ollamamcp-chat-section

// Add JavaScript inline for now
echo html_writer::start_tag('script');
echo "
function sendMessage() {
    var input = document.getElementById('chat-input');
    var message = input.value.trim();
    
    if (!message) return;
    
    console.log('Sending message:', message);
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    addMessage('...', 'assistant typing');
    
    // Test with a simple AJAX call first
    var testData = [{
        methodname: 'local_ollamamcp_send_message',
        args: {
            message: message,
            courseid: " . $courseid . "
        }
    }];
    
    console.log('Sending data:', testData);
    console.log('To URL:', '" . $CFG->wwwroot . "/lib/ajax/service.php');
    
    // Send to server - use Moodle external API (proper architecture)
    fetch('" . $CFG->wwwroot . "/lib/ajax/service.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(testData)
    })
    .then(function(response) {
        console.log('Raw response status:', response.status);
        console.log('Raw response headers:', response.headers);
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Parsed data:', data);
        
        // Remove typing indicator
        var typingMsg = document.querySelector('.message.typing');
        if (typingMsg) typingMsg.remove();
        
        if (data && data[0] && data[0].data && data[0].data.response) {
            addMessage(data[0].data.response, 'assistant');
        } else if (data && data[0] && data[0].error) {
            addMessage('Error: ' + data[0].error, 'assistant');
        } else {
            addMessage('Sorry, I could not process your request. Please try again.', 'assistant');
            console.log('Invalid response structure:', data);
        }
    })
    .catch(function(error) {
        console.log('Fetch error:', error);
        
        // Remove typing indicator
        var typingMsg = document.querySelector('.message.typing');
        if (typingMsg) typingMsg.remove();
        
        addMessage('Connection error: ' + error.message, 'assistant');
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
                sendMessage();
            }
        });
    }
});
";
echo html_writer::end_tag('script');

echo $OUTPUT->footer();
