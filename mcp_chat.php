<?php
require_once('../../config.php');

// Get courseid parameter - make it optional to allow direct access
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid > 0) {
    // Course context requested
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($courseid);
    require_capability('local/ollamamcp:use', $context);
    
    $PAGE->set_url('/local/ollamamcp/mcp_chat.php', ['courseid' => $courseid]);
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('pluginname', 'local_ollamamcp'));
    $PAGE->set_heading($course->fullname);
} else {
    // System context - no specific course
    require_login();
    $context = context_system::instance();
    
    $PAGE->set_url('/local/ollamamcp/mcp_chat.php');
    $PAGE->set_context($context);
    $PAGE->set_title('Ollama MCP - AI Assistant');
    $PAGE->set_heading('AI Assistant');
}

// Check if plugin is enabled
if (!get_config('local_ollamamcp', 'enabled')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('Plugin is disabled', 'error');
    echo $OUTPUT->footer();
    exit;
}

// Check if web services are enabled
if (!get_config('core', 'enablewebservices')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('Web services are not enabled', 'error');
    echo $OUTPUT->footer();
    exit;
}

// Check if user has required capability
if (!has_capability('moodle/site:config', $context) && !has_capability('local/ollamamcp:use', $context)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('You do not have permission to access the Ollama AI Assistant. Please contact your Moodle administrator to get the required permissions.', 'error');
    echo $OUTPUT->footer();
    exit;
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
    
    // Check what type of Moodle data the user is asking about
    var isCourseQuery = message.toLowerCase().match(/courses?|classes?|subjects?|topics?|what.*available|list.*courses/);
    var isActivityQuery = message.toLowerCase().match(/activities?|assignments?|quizzes?|forums?|resources?|materials?/);
    var isUserQuery = message.toLowerCase().match(/users?|students?|teachers?|participants?|enrolled/);
    var isCategoryQuery = message.toLowerCase().match(/categories?|departments?|subjects?|areas/);
    var isStatsQuery = message.toLowerCase().match(/statistics?|stats?|numbers?|count|total|how many/);
    var isPlatformQuery = message.toLowerCase().match(/platform|platform info|site info|moodle info|system info|about.*platform|about.*site/);
    
    var apiType = 'general';
    var apiParams = {};
    
    if (isCourseQuery) {
        apiType = 'courses';
        apiParams = {type: 'courses', limit: 15};
    } else if (isActivityQuery) {
        apiType = 'activities';
        apiParams = {type: 'activities'};
    } else if (isUserQuery) {
        apiType = 'users';
        apiParams = {type: 'users', limit: 10};
    } else if (isCategoryQuery) {
        apiType = 'categories';
        apiParams = {type: 'categories'};
    } else if (isStatsQuery) {
        apiType = 'stats';
        apiParams = {type: 'stats'};
    } else if (isPlatformQuery) {
        apiType = 'platform';
        apiParams = {type: 'platform'};
    }
    
    // Always get platform validation info first
    fetch('" . $CFG->wwwroot . "/local/ollamamcp/moodle_api.php?type=platform')
    .then(function(platformResponse) {
        console.log('Platform API response status:', platformResponse.status);
        return platformResponse.json();
    })
    .then(function(platformData) {
        console.log('Platform API data:', platformData);
        if (platformData.success) {
            var platformInfo = platformData.data;
            
            // If it's a Moodle-specific query, get the data first
            if (apiType !== 'general') {
                var apiUrl = '" . $CFG->wwwroot . "/local/ollamamcp/moodle_api.php';
                var queryString = Object.keys(apiParams).map(key => key + '=' + apiParams[key]).join('&');
                
                console.log('Fetching Moodle data from:', apiUrl + '?' + queryString);
                
                fetch(apiUrl + '?' + queryString)
                .then(function(response) {
                    console.log('Moodle data API response status:', response.status);
                    return response.json();
                })
                .then(function(moodleData) {
                    console.log('Moodle data received:', moodleData);
                    if (moodleData.success) {
                        var contextInfo = getContextInfo(moodleData, apiType);
                        var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, contextInfo, apiType, message);
                        console.log('Enhanced prompt created:', enhancedPrompt);
                        
                        // Send enhanced prompt with real Moodle data to Ollama
                        sendToOllama(enhancedPrompt, model);
                    } else {
                        console.log('Moodle data API failed, using platform-only');
                        // Fallback to platform-only prompt
                        var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                        sendToOllama(enhancedPrompt, model);
                    }
                })
                .catch(function(error) {
                    console.log('Error fetching Moodle data:', error);
                    // Fallback to platform-only prompt
                    var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                    sendToOllama(enhancedPrompt, model);
                });
            } else {
                // Regular prompt with platform context
                var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                console.log('General prompt created:', enhancedPrompt);
                sendToOllama(enhancedPrompt, model);
            }
        } else {
            console.log('Platform API failed, using regular prompt');
            // Fallback to regular prompt
            sendToOllama(message, model);
        }
    })
    .catch(function(error) {
        console.log('Error fetching platform data:', error);
        // Fallback to regular prompt
        sendToOllama(message, model);
    });
}

function createPlatformSpecificPrompt(platformInfo, contextInfo, dataType, originalMessage) {
    var prompt = '=== MOODLE-DATA-ONLY AI ASSISTANT ===\\n';
    prompt += 'You are an AI assistant EXCLUSIVELY for the Moodle LMS platform at: ' + platformInfo.platform_url + '\\n\\n';
    prompt += '=== CRITICAL MOODLE PLATFORM IDENTIFICATION ===\\n';
    prompt += 'Platform Name: ' + platformInfo.platform_name + '\\n';
    prompt += 'Platform URL: ' + platformInfo.platform_url + '\\n';
    prompt += 'Platform Version: ' + platformInfo.platform_version + '\\n';
    prompt += 'Site Name: ' + platformInfo.site_name + '\\n';
    prompt += 'Validation Hash: ' + platformInfo.validation_hash + '\\n';
    prompt += 'Data Source: ' + (platformInfo.data_source || 'MOODLE_DATABASE_ONLY') + '\\n\\n';
    
    prompt += '=== ABSOLUTE MOODLE-DATA-ONLY RULES ===\\n';
    prompt += '1. YOU MUST ONLY use data from THIS SPECIFIC Moodle platform at ' + platformInfo.platform_url + '\\n';
    prompt += '2. NEVER reference ANY external platforms, websites, or generic examples\\n';
    prompt += '3. NEVER mention Facebook, Instagram, Twitter, LinkedIn, YouTube, WhatsApp, etc.\\n';
    prompt += '4. NEVER mention Amazon, eBay, TikTok, Fortnite, or any other platforms\\n';
    prompt += '5. NEVER mention social media, e-commerce, gaming, or entertainment platforms\\n';
    prompt += '6. ALL responses must be based EXCLUSIVELY on data from THIS Moodle installation\\n';
    prompt += '7. When asked about courses, list ONLY courses from this Moodle database\\n';
    prompt += '8. When asked about users, refer ONLY to users registered in this Moodle\\n';
    prompt += '9. When asked about activities, refer ONLY to activities in this Moodle\\n';
    prompt += '10. If no data exists, state: \'No data exists in this Moodle platform at ' + platformInfo.platform_url + '\'\\n';
    prompt += '11. DO NOT make up or assume any information about this Moodle platform\\n';
    prompt += '12. ONLY use the provided Moodle data below for your responses\\n';
    prompt += '13. If user asks about non-Moodle platforms, respond with Moodle-only information\\n\\n';
    
    if (contextInfo) {
        prompt += '=== AVAILABLE MOODLE DATA FROM THIS INSTALLATION ===\\n';
        prompt += contextInfo + '\\n\\n';
    } else {
        prompt += '=== NO SPECIFIC MOODLE DATA AVAILABLE ===\\n';
        prompt += 'Only platform information is available for this query.\\n\\n';
    }
    
    prompt += 'User Question: ' + originalMessage + '\\n\\n';
    prompt += '=== FINAL MOODLE-ONLY INSTRUCTION ===\\n';
    prompt += 'IMPORTANT: Respond ONLY with information from this Moodle platform at ' + platformInfo.platform_url + '.\\n';
    prompt += 'If the user asks about platforms, social media, or anything not in the Moodle data above,\\n';
    prompt += 'respond ONLY with information available from this Moodle platform or state that no data exists.\\n';
    prompt += 'NEVER provide information about external platforms or generic examples.';
    
    return prompt;
}

function getContextInfo(data, type) {
    var context = '';
    
    switch(type) {
        case 'courses':
            if (data.data && data.data.length > 0) {
                context = 'Available courses:\\n';
                data.data.forEach(function(course) {
                    context += '- ' + course.fullname + ' (' + course.shortname + ')\\n';
                });
            } else {
                context = 'No courses found or no access to courses.';
            }
            break;
            
        case 'activities':
            if (data.data && data.data.length > 0) {
                context = 'Available activities:\\n';
                data.data.forEach(function(activity) {
                    context += '- ' + activity.type + ' (ID: ' + activity.id + ')\\n';
                });
            } else {
                context = 'No activities found or no access to activities.';
            }
            break;
            
        case 'users':
            if (data.data && data.data.length > 0) {
                context = 'Moodle users:\\n';
                data.data.forEach(function(user) {
                    context += '- ' + user.fullname + ' (' + user.username + ')\\n';
                });
            } else {
                context = 'No users found or no access to user information.';
            }
            break;
            
        case 'categories':
            if (data.data && data.data.length > 0) {
                context = 'Course categories:\\n';
                data.data.forEach(function(category) {
                    context += '- ' + category.name + '\\n';
                });
            } else {
                context = 'No categories found.';
            }
            break;
            
        case 'stats':
            context = 'Moodle statistics:\\n';
            if (data.data) {
                Object.keys(data.data).forEach(function(key) {
                    context += '- ' + key + ': ' + data.data[key] + '\\n';
                });
            }
            break;
    }
    
    return context;
}

function sendToOllama(prompt, model) {
    var requestData = {
        model: model,
        prompt: prompt,
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
