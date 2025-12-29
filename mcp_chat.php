<?php
require_once('../../config.php');
global $DB, $CFG;

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
echo '<style>
.moodle-chat-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px 15px 0 0;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.chat-header h2 {
    margin: 0;
    font-size: 2.5em;
    font-weight: 300;
}

.chat-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.status-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
}

.status-card h3 {
    color: #333;
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-card .status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.chat-interface {
    background: white;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chat-messages {
    height: 500px;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.message {
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.message.user {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    flex-shrink: 0;
}

.message.user .message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.message.assistant .message-avatar {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.message-content {
    max-width: 70%;
    padding: 15px 20px;
    border-radius: 20px;
    position: relative;
}

.message.user .message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.message.assistant .message-content {
    background: white;
    border: 1px solid #e9ecef;
    color: #333;
}

.message-time {
    font-size: 0.8em;
    opacity: 0.7;
    margin-top: 5px;
}

.chat-input-area {
    padding: 20px;
    background: white;
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.chat-input {
    flex: 1;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    padding: 15px 20px;
    font-size: 16px;
    resize: none;
    transition: border-color 0.3s ease;
}

.chat-input:focus {
    outline: none;
    border-color: #667eea;
}

.send-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.send-button:hover {
    transform: scale(1.1);
}

.send-button:active {
    transform: scale(0.95);
}

.typing-indicator {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 15px 20px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #6c757d;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-10px); }
}

.server-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    border-radius: 20px;
    font-size: 0.9em;
    margin-top: 15px;
}

.server-status.online {
    background: #d4edda;
    color: #155724;
}

.server-status.offline {
    background: #f8d7da;
    color: #721c24;
}

.server-status.checking {
    background: #fff3cd;
    color: #856404;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.action-button {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.9em;
    cursor: pointer;
    transition: all 0.3s ease;
}

.action-button:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.quick-prompts {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
}

.quick-prompts h4 {
    margin: 0 0 10px 0;
    color: #495057;
    font-size: 0.9em;
}

.prompt-suggestions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.prompt-suggestion {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 15px;
    padding: 6px 12px;
    font-size: 0.85em;
    cursor: pointer;
    transition: all 0.2s ease;
}

.prompt-suggestion:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
</style>';

echo '<div class="moodle-chat-container">';

echo '<div class="chat-header">';
echo '<h2>🤖 Moodle AI Assistant</h2>';
echo '<p>Powered by Ollama • Using only Moodle platform data</p>';
echo '</div>';

// Enhanced Ollama Server status
$ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';

echo '<div class="status-card">';
echo '<h3><span class="status-indicator"></span> Ollama Server Status</h3>';
echo '<p><strong>Server URL:</strong> <code>' . htmlspecialchars($ollama_url) . '</code></p>';
echo '<p><strong>Default Model:</strong> <code>' . htmlspecialchars(get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest') . '</code></p>';
echo '<div id="ollama-status" class="server-status checking">🔄 Checking server connection...</div>';

echo '<div class="action-buttons">';
echo '<button class="action-button" onclick="checkOllamaServer()">🔍 Check Server</button>';
echo '<button class="action-button" onclick="listModels()">📋 List Models</button>';
echo '<button class="action-button" onclick="clearChat()">🗑️ Clear Chat</button>';
echo '<button class="action-button" onclick="loadChatHistory()">📜 Load History</button>';
echo '<button class="action-button" onclick="showChatStats()">📊 Statistics</button>';
echo '</div>';

echo '<div class="quick-prompts">';
echo '<h4>💡 Quick Prompts:</h4>';
echo '<div class="prompt-suggestions">';
echo '<span class="prompt-suggestion" onclick="sendQuickPrompt(\'list platform info\')">📊 Platform Info</span>';
echo '<span class="prompt-suggestion" onclick="sendQuickPrompt(\'list courses\')">📚 List Courses</span>';
echo '<span class="prompt-suggestion" onclick="sendQuickPrompt(\'list users\')">👥 List Users</span>';
echo '<span class="prompt-suggestion" onclick="sendQuickPrompt(\'list activities\')">📝 List Activities</span>';
echo '<span class="prompt-suggestion" onclick="sendQuickPrompt(\'show statistics\')">📈 Show Statistics</span>';
echo '</div>';
echo '</div>';

echo '</div>';

// Enhanced JavaScript for Ollama server status check
$PAGE->requires->js_init_code("
    function checkOllamaServer() {
        var statusDiv = document.getElementById('ollama-status');
        statusDiv.className = 'server-status checking';
        statusDiv.innerHTML = '🔄 Checking Ollama server...';
        
        var ollamaUrl = '" . $ollama_url . "';
        
        // Check Ollama API /api/tags endpoint
        fetch(ollamaUrl + '/api/tags', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response) {
            if (response.ok) {
                statusDiv.className = 'server-status online';
                statusDiv.innerHTML = '✅ Ollama Server is running and connected to ' + ollamaUrl;
            } else {
                throw new Error('HTTP ' + response.status);
            }
        }).catch(function(error) {
            statusDiv.className = 'server-status offline';
            statusDiv.innerHTML = '⚠️ Ollama Server is not running. Please start Ollama server: <code>ollama serve</code>';
        });
    }
    
    // Auto-check status when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkOllamaServer, 1000);
    });
");

echo html_writer::tag('hr', '');

echo '<div class="chat-interface">';

// Enhanced chat messages area
echo '<div class="chat-messages" id="chat-messages">';
echo '<div class="message assistant">';
echo '<div class="message-avatar">🤖</div>';
echo '<div class="message-content">';
echo '<div>Hello! I\'m your Moodle AI assistant. I connect directly to the Ollama server and use only data from this Moodle platform.</div>';
echo '<div class="message-time">' . date('H:i') . '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Enhanced chat input area
echo '<div class="chat-input-area">';
echo '<textarea id="chat-input" class="chat-input" placeholder="Type your message here..." rows="3"></textarea>';
echo '<button class="send-button" onclick="sendOllamaMessage()">➤</button>';
echo '</div>';

echo '</div>'; // chat-interface
echo '</div>'; // moodle-chat-container

// Enhanced JavaScript functions with chat storage
echo html_writer::start_tag('script');
echo "
var ollamaUrl = '" . $ollama_url . "';
var chatApiUrl = '" . $CFG->wwwroot . "/local/ollamamcp/chat_api.php';
var currentSessionId = '';
var currentCourseId = " . ($courseid ?? 0) . ";

// Initialize or load session
function initializeSession() {
    currentSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    loadChatHistory();
}

function sendQuickPrompt(prompt) {
    document.getElementById('chat-input').value = prompt;
    sendOllamaMessage();
}

function clearChat() {
    if (confirm('Are you sure you want to clear the chat history?')) {
        // Clear from database
        if (currentSessionId) {
            fetch(chatApiUrl + '?action=clear&session_id=' + encodeURIComponent(currentSessionId) + '&courseid=' + currentCourseId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Chat history cleared from database');
                }
            });
        }
        
        // Clear from UI
        var messagesDiv = document.getElementById('chat-messages');
        messagesDiv.innerHTML = '<div class=\"message assistant\"><div class=\"message-avatar\">🤖</div><div class=\"message-content\"><div>Chat cleared. How can I help you?</div><div class=\"message-time\">' + new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</div></div></div>';
    }
}

function loadChatHistory() {
    fetch(chatApiUrl + '?action=get&courseid=' + currentCourseId + '&limit=20')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.history && data.history.length > 0) {
            var messagesDiv = document.getElementById('chat-messages');
            messagesDiv.innerHTML = '';
            
            data.history.forEach(function(msg) {
                addMessage(msg.message, msg.type, false, msg.formatted_time);
            });
            
            console.log('Loaded ' + data.history.length + ' messages from history');
        } else if (data.action_required) {
            console.log('Chat history unavailable - database table needs to be created');
            // Don't show error to user - just start fresh chat
        }
    })
    .catch(error => {
        console.log('Error loading chat history:', error);
        // Silently handle - chat starts fresh
    });
}

function showChatStats() {
    fetch(chatApiUrl + '?action=stats&courseid=' + currentCourseId)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.statistics) {
            var stats = data.statistics;
            var statsHtml = '📊 Chat Statistics:\\n';
            statsHtml += 'Total Messages: ' + stats.total_messages + '\\n';
            statsHtml += 'User Messages: ' + stats.user_messages + '\\n';
            statsHtml += 'Assistant Messages: ' + stats.assistant_messages + '\\n';
            statsHtml += 'Total Sessions: ' + stats.total_sessions + '\\n';
            statsHtml += 'Avg Response Time: ' + Math.round(stats.avg_response_time || 0) + 'ms\\n';
            statsHtml += 'Total Tokens Used: ' + (stats.total_tokens || 0);
            
            alert(statsHtml);
        }
    })
    .catch(error => {
        console.log('Error loading statistics:', error);
        alert('Error loading statistics');
    });
}

function saveMessageToDatabase(messageType, message, contextData, modelUsed, responseTime, tokensUsed) {
    var data = {
        action: 'save',
        message_type: messageType,
        message: message,
        context_data: contextData ? JSON.stringify(contextData) : '',
        model_used: modelUsed,
        response_time: responseTime,
        tokens_used: tokensUsed,
        session_id: currentSessionId,
        courseid: currentCourseId
    };
    
    fetch(chatApiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            currentSessionId = result.session_id;
            console.log('Message saved to database');
        } else {
            console.log('Failed to save message:', result.error);
            // Don't show error to user - chat continues without storage
            if (result.action_required) {
                console.log('Action required:', result.action_required);
            }
        }
    })
    .catch(error => {
        // Silently handle database errors - chat continues without storage
        console.log('Database storage unavailable - chat continuing without saving:', error.message);
    });
}

function listModels() {
    var statusDiv = document.getElementById('ollama-status');
    statusDiv.className = 'server-status checking';
    statusDiv.innerHTML = '🔄 Fetching available models...';
    
    fetch(ollamaUrl + '/api/tags')
    .then(function(response) {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('HTTP ' + response.status);
        }
    })
    .then(function(data) {
        var models = data.models || [];
        var modelList = models.map(function(model) { return model.name; }).join(', ');
        statusDiv.className = 'server-status online';
        statusDiv.innerHTML = '✅ Available Models: ' + (modelList || 'No models found');
    })
    .catch(function(error) {
        statusDiv.className = 'server-status offline';
        statusDiv.innerHTML = '❌ Error fetching models: ' + error.message;
    });
}

function addMessage(content, type, isTyping = false, time = null) {
    var messagesDiv = document.getElementById('chat-messages');
    var messageDiv = document.createElement('div');
    messageDiv.className = 'message ' + type;
    
    var avatar = type === 'user' ? '👤' : '🤖';
    var messageTime = time || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    var contentHtml = isTyping ? 
        '<div class=\"typing-indicator\"><div class=\"typing-dot\"></div><div class=\"typing-dot\"></div><div class=\"typing-dot\"></div></div>' :
        '<div>' + content + '</div><div class=\"message-time\">' + messageTime + '</div>';
    
    messageDiv.innerHTML = 
        '<div class=\"message-avatar\">' + avatar + '</div>' +
        '<div class=\"message-content\">' + contentHtml + '</div>';
    
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    return messageDiv;
}

function sendOllamaMessage() {
    var input = document.getElementById('chat-input');
    var message = input.value.trim();
    
    if (!message) return;
    
    console.log('Sending Ollama message:', message);
    
    // Save user message to database
    saveMessageToDatabase('user', message, null, null, 0, 0);
    
    // Add user message to UI
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    var typingMessage = addMessage('', 'assistant', true);
    
    // Send to Ollama API
    sendOllamaRequest(message, typingMessage);
}

function sendOllamaRequest(message, typingMessage) {
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
                        sendToOllama(enhancedPrompt, model, typingMessage, platformInfo, contextInfo, apiType);
                    } else {
                        console.log('Moodle data API failed, using platform-only');
                        // Fallback to platform-only prompt
                        var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                        sendToOllama(enhancedPrompt, model, typingMessage, platformInfo, '', 'general');
                    }
                })
                .catch(function(error) {
                    console.log('Error fetching Moodle data:', error);
                    // Fallback to platform-only prompt
                    var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                    sendToOllama(enhancedPrompt, model, typingMessage, platformInfo, '', 'general');
                });
            } else {
                // Regular prompt with platform context
                var enhancedPrompt = createPlatformSpecificPrompt(platformInfo, '', 'general', message);
                console.log('General prompt created:', enhancedPrompt);
                sendToOllama(enhancedPrompt, model, typingMessage, platformInfo, '', 'general');
            }
        } else {
            console.log('Platform API failed, using regular prompt');
            // Fallback to regular prompt
            sendToOllama(message, model, typingMessage, null, null, 'general');
        }
    })
    .catch(function(error) {
        console.log('Error fetching platform data:', error);
        // Fallback to regular prompt
        sendToOllama(message, model, typingMessage, null, null, 'general');
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

function sendToOllama(prompt, model, typingMessage, platformInfo, contextInfo, apiType) {
    var startTime = Date.now();

    var data = {
        model: model,
        prompt: prompt,
        stream: false,
        options: {
            temperature: 0.7,
            top_p: 0.9,
            num_predict: 500
        }
    };

    console.log('Ollama Request:', data);

    fetch(ollamaUrl + '/api/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(function(response) {
        console.log('Ollama Response status:', response.status);
        return response.json();
    })
    .then(function(result) {
        console.log('Ollama Response data:', result);

        var responseTime = Date.now() - startTime;
        var response = result.response || 'Sorry, I could not process your request.';

        // Remove typing indicator
        if (typingMessage) {
            typingMessage.remove();
        }

        // Add assistant response to UI
        addMessage(response, 'assistant');

        // Save assistant response to database
        var contextData = {
            platform_info: platformInfo,
            context_info: contextInfo,
            prompt_type: apiType || 'general'
        };

        saveMessageToDatabase('assistant', response, contextData, model, responseTime, 0);
    })
    .catch(function(error) {
        console.log('Ollama Error:', error);
        
        // Remove typing indicator
        if (typingMessage) {
            typingMessage.remove();
        } else {
            // Fallback: find any typing message
            var typingMsg = document.querySelector('.message.typing');
            if (typingMsg) typingMsg.remove();
        }
        
        addMessage('Ollama Connection Error: ' + error.message, 'assistant');
        
        // Save error to database
        saveMessageToDatabase('assistant', 'Ollama Connection Error: ' + error.message, null, model, 0, 0);
    });
}

// Handle Enter key in textarea and initialize session
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
    
    // Initialize chat session
    initializeSession();
});
";
echo html_writer::end_tag('script');

echo $OUTPUT->footer();
?>
