define(['jquery'], function($) {
    return {
        init: function(config) {
            // Store configuration for API calls
            this.config = config;

            // Create chat interface
            var container = $('#ollamamcp-container');
            if (container.length === 0) {
                return;
            }

            var chatHtml = `
                <div class="ollamamcp-chat">
                    <div class="chat-header">
                        <div class="header-left">
                            <div class="ai-avatar">🤖</div>
                            <div class="header-info">
                                <h4>Moodle AI Assistant</h4>
                                <span class="status-indicator online">● Online</span>
                            </div>
                        </div>
                        <button class="toggle-chat" title="Toggle chat">−</button>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="message assistant">
                            <div class="message-avatar">🤖</div>
                            <div class="message-content">
                                <div class="message-text">Hello! I'm your Moodle AI assistant. I can help you with:</div>
                                <div class="message-features">
                                    <div class="feature-item">📚 Moodle development questions</div>
                                    <div class="feature-item">🔍 Documentation search</div>
                                    <div class="feature-item">⚙️ Plugin configuration</div>
                                    <div class="feature-item">🎓 Course management</div>
                                </div>
                                <div class="message-text">How can I help you today?</div>
                            </div>
                            <div class="message-time">${new Date().toLocaleTimeString()}</div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <div class="quick-prompts">
                            <div class="prompts-header">💡 Quick Prompts:</div>
                            <div class="prompts-grid">
                                <button class="prompt-btn" data-prompt="📊 Platform Info">📊 Platform Info</button>
                                <button class="prompt-btn" data-prompt="📚 List Courses">📚 List Courses</button>
                                <button class="prompt-btn" data-prompt="👥 List Users">👥 List Users</button>
                                <button class="prompt-btn" data-prompt="📝 List Activities">📝 List Activities</button>
                                <button class="prompt-btn" data-prompt="📈 Show Statistics">📈 Show Statistics</button>
                                <button class="prompt-btn" data-prompt="🔍 Check Server">🔍 Check Server</button>
                                <button class="prompt-btn" data-prompt="📋 List Models">📋 List Models</button>
                                <button class="prompt-btn" data-prompt="🗑️ Clear Chat">🗑️ Clear Chat</button>
                            </div>
                        </div>
                        <div class="input-container">
                            <textarea id="chat-input" placeholder="Ask me anything about Moodle..." rows="2"></textarea>
                            <button id="send-message" class="send-btn" title="Send message">
                                <span class="send-icon">➤</span>
                            </button>
                        </div>
                        <div class="input-footer">
                            <span class="help-text">Press Enter to send, Shift+Enter for new line</span>
                        </div>
                    </div>
                </div>
            `;

            container.html(chatHtml);

            // Add enhanced styling
            var style = `
                <style>
                    .ollamamcp-chat {
                        border: 1px solid #e1e5e9;
                        border-radius: 12px;
                        height: 600px;
                        display: flex;
                        flex-direction: column;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background: #ffffff;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                    }
                    .chat-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        padding: 15px 20px;
                        border-bottom: 1px solid #e1e5e9;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        color: white;
                    }
                    .header-left {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }
                    .ai-avatar {
                        width: 40px;
                        height: 40px;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 20px;
                        backdrop-filter: blur(10px);
                    }
                    .header-info h4 {
                        margin: 0;
                        font-size: 16px;
                        font-weight: 600;
                    }
                    .status-indicator {
                        display: inline-flex;
                        align-items: center;
                        font-size: 11px;
                        opacity: 0.9;
                        margin-top: 2px;
                    }
                    .status-indicator.online {
                        color: #4ade80;
                    }
                    .toggle-chat {
                        background: rgba(255, 255, 255, 0.2);
                        border: none;
                        font-size: 20px;
                        cursor: pointer;
                        padding: 8px;
                        border-radius: 6px;
                        color: white;
                        transition: background 0.2s;
                    }
                    .toggle-chat:hover {
                        background: rgba(255, 255, 255, 0.3);
                    }
                    .chat-messages {
                        flex: 1;
                        padding: 20px;
                        overflow-y: auto;
                        background: #f8fafc;
                        scroll-behavior: smooth;
                    }
                    .message {
                        margin-bottom: 20px;
                        display: flex;
                        gap: 12px;
                        animation: fadeInUp 0.3s ease-out;
                    }
                    @keyframes fadeInUp {
                        from {
                            opacity: 0;
                            transform: translateY(10px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    .message.user {
                        flex-direction: row-reverse;
                    }
                    .message-avatar {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        flex-shrink: 0;
                    }
                    .message.assistant .message-avatar {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    .message.user .message-avatar {
                        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                    }
                    .message-content {
                        flex: 1;
                        max-width: calc(100% - 44px);
                    }
                    .message-text {
                        background: white;
                        padding: 12px 16px;
                        border-radius: 12px;
                        margin-bottom: 8px;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                        line-height: 1.5;
                    }
                    .message.user .message-text {
                        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                        color: white;
                    }
                    .message-features {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 8px;
                        margin: 12px 0;
                    }
                    .feature-item {
                        background: rgba(102, 126, 234, 0.1);
                        padding: 8px 12px;
                        border-radius: 8px;
                        font-size: 13px;
                        border-left: 3px solid #667eea;
                    }
                    .message-time {
                        font-size: 11px;
                        color: #64748b;
                        text-align: right;
                        margin-top: 4px;
                    }
                    .message.user .message-time {
                        text-align: left;
                    }
                    .chat-input {
                        padding: 20px;
                        border-top: 1px solid #e1e5e9;
                        background: white;
                    }
                    .input-container {
                        display: flex;
                        gap: 12px;
                        align-items: flex-end;
                    }
                    .chat-input textarea {
                        flex: 1;
                        border: 2px solid #e1e5e9;
                        border-radius: 12px;
                        padding: 12px 16px;
                        resize: none;
                        font-family: inherit;
                        font-size: 14px;
                        line-height: 1.5;
                        transition: border-color 0.2s;
                        background: #f8fafc;
                    }
                    .chat-input textarea:focus {
                        outline: none;
                        border-color: #667eea;
                        background: white;
                    }
                    .send-btn {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        border-radius: 12px;
                        width: 48px;
                        height: 48px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: transform 0.2s, box-shadow 0.2s;
                    }
                    .send-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                    }
                    .send-btn:active {
                        transform: translateY(0);
                    }
                    .send-icon {
                        font-size: 18px;
                    }
                    .input-footer {
                        margin-top: 8px;
                        text-align: center;
                    }
                    .help-text {
                        font-size: 12px;
                        color: #64748b;
                    }
                    .message.typing .message-text {
                        background: #f1f5f9;
                        color: #64748b;
                        font-style: italic;
                    }
                    .message.typing .message-text::after {
                        content: '';
                        display: inline-block;
                        width: 20px;
                        animation: typing 1.4s infinite;
                    }
                    @keyframes typing {
                        0%, 60%, 100% { content: '.'; }
                        30% { content: '..'; }
                    }
                    /* Scrollbar styling */
                    .chat-messages::-webkit-scrollbar {
                        width: 6px;
                    }
                    .chat-messages::-webkit-scrollbar-track {
                        background: #f1f5f9;
                    }
                    .chat-messages::-webkit-scrollbar-thumb {
                        background: #cbd5e1;
                        border-radius: 3px;
                    }
                    .chat-messages::-webkit-scrollbar-thumb:hover {
                        background: #94a3b8;
                    }
                    
                    /* Message formatting styles */
                    .styled-list {
                        margin: 12px 0;
                        padding: 0;
                    }
                    .bullet-item, .number-item {
                        background: rgba(102, 126, 234, 0.05);
                        padding: 8px 12px;
                        margin: 4px 0;
                        border-radius: 6px;
                        border-left: 3px solid #667eea;
                        font-size: 14px;
                        line-height: 1.4;
                    }
                    .stats-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 12px;
                        margin: 16px 0;
                    }
                    .stat-item {
                        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                        padding: 12px 16px;
                        border-radius: 8px;
                        border-left: 4px solid #667eea;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                    }
                    .stat-label {
                        font-weight: 600;
                        color: #475569;
                        font-size: 12px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        display: block;
                        margin-bottom: 4px;
                    }
                    .stat-value {
                        font-weight: 700;
                        color: #1e293b;
                        font-size: 16px;
                    }
                    .platform-details {
                        background: rgba(102, 126, 234, 0.03);
                        border-radius: 8px;
                        padding: 16px;
                        margin: 12px 0;
                        border: 1px solid rgba(102, 126, 234, 0.1);
                    }
                    .platform-info {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 8px 0;
                        border-bottom: 1px solid rgba(102, 126, 234, 0.1);
                    }
                    .platform-info:last-child {
                        border-bottom: none;
                    }
                    .info-label {
                        font-weight: 600;
                        color: #64748b;
                        font-size: 13px;
                    }
                    .info-value {
                        font-weight: 500;
                        color: #1e293b;
                        font-family: 'Courier New', monospace;
                        background: rgba(102, 126, 234, 0.1);
                        padding: 2px 8px;
                        border-radius: 4px;
                        font-size: 12px;
                    }
                    .section-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 12px 16px;
                        border-radius: 8px;
                        font-weight: 600;
                        margin: 16px 0 12px 0;
                        font-size: 14px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    .main-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 16px 20px;
                        border-radius: 12px;
                        font-weight: 700;
                        margin: 20px 0 16px 0;
                        font-size: 16px;
                        text-align: center;
                        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);
                    }
                    .code-block {
                        background: #1e293b;
                        color: #e2e8f0;
                        padding: 16px;
                        border-radius: 8px;
                        margin: 12px 0;
                        overflow-x: auto;
                        font-family: 'Courier New', monospace;
                        font-size: 13px;
                        line-height: 1.4;
                    }
                    .code-block pre {
                        margin: 0;
                        white-space: pre-wrap;
                    }
                    .inline-code {
                        background: rgba(102, 126, 234, 0.1);
                        color: #667eea;
                        padding: 2px 6px;
                        border-radius: 4px;
                        font-family: 'Courier New', monospace;
                        font-size: 12px;
                        font-weight: 600;
                    }
                    .chat-link {
                        color: #667eea;
                        text-decoration: none;
                        border-bottom: 1px solid transparent;
                        transition: border-color 0.2s;
                    }
                    .chat-link:hover {
                        border-bottom-color: #667eea;
                    }
                    strong {
                        color: #1e293b;
                        font-weight: 600;
                    }
                    em {
                        color: #64748b;
                        font-style: italic;
                    }
                    
                    /* Quick Prompts Styling */
                    .quick-prompts {
                        background: #f8fafc;
                        border-top: 1px solid #e2e8f0;
                        padding: 12px 16px;
                    }
                    .prompts-header {
                        font-size: 12px;
                        font-weight: 600;
                        color: #64748b;
                        margin-bottom: 8px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    .prompts-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                        gap: 6px;
                    }
                    .prompt-btn {
                        background: white;
                        border: 1px solid #e2e8f0;
                        border-radius: 6px;
                        padding: 6px 8px;
                        font-size: 11px;
                        font-weight: 500;
                        color: #475569;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        text-align: center;
                        line-height: 1.2;
                    }
                    .prompt-btn:hover {
                        background: #667eea;
                        color: white;
                        border-color: #667eea;
                        transform: translateY(-1px);
                        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
                    }
                    .prompt-btn:active {
                        transform: translateY(0);
                        box-shadow: 0 1px 2px rgba(102, 126, 234, 0.2);
                    }
                </style>
            `;

            $('head').append(style);

            // Handle message sending
            var self = this;
            $('#send-message').click(function() {
                sendMessage.call(self);
            });
            
            // Handle quick prompt buttons
            $('.prompt-btn').click(function() {
                var prompt = $(this).data('prompt');
                handleQuickPrompt.call(self, prompt);
            });

            $('#chat-input').keypress(function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage.call(self);
                }
            });

            /**
             * Handle quick prompt button clicks
             * @param {string} prompt - The prompt text
             */
            function handleQuickPrompt(prompt) {
                var self = this;
                
                switch(prompt) {
                    case '📊 Platform Info':
                        sendMessage.call(self, 'Show me platform information');
                        break;
                    case '📚 List Courses':
                        sendMessage.call(self, 'List all available courses');
                        break;
                    case '👥 List Users':
                        sendMessage.call(self, 'List all users in the system');
                        break;
                    case '📝 List Activities':
                        sendMessage.call(self, 'List all activities from all courses');
                        break;
                    case '📈 Show Statistics':
                        sendMessage.call(self, 'Show me system statistics');
                        break;
                    case '🔍 Check Server':
                        sendMessage.call(self, 'Check server status and connectivity');
                        break;
                    case '📋 List Models':
                        sendMessage.call(self, 'List available AI models');
                        break;
                    case '🗑️ Clear Chat':
                        clearChat.call(self);
                        break;
                    default:
                        sendMessage.call(self, prompt);
                        break;
                }
            }
            
            /**
             * Clear the chat history
             */
            function clearChat() {
                $('#chat-messages').empty();
                addMessage('Chat history cleared. How can I help you?', 'assistant');
            }

            /**
             * Send a message to the AI assistant
             * @param {string} customMessage - Optional custom message to send
             */
            function sendMessage(customMessage) {
                var input = $('#chat-input');
                var message = customMessage || input.val().trim();

                if (!message) {
                    return;
                }

                // Clear input if not using custom message
                if (!customMessage) {
                    input.val('');
                }

                // Add user message
                addMessage(message, 'user');

                // Show typing indicator
                addMessage('Thinking...', 'assistant typing');

                // Make real API call to Moodle
                $.ajax({
                    url: self.config.wwwroot + '/lib/ajax/service.php',
                    method: 'POST',
                    data: JSON.stringify([{
                        methodname: 'local_ollamamcp_send_message',
                        args: {
                            message: message,
                            courseid: self.config.courseid
                        }
                    }]),
                    contentType: 'application/json',
                    success: function(response) {
                        // Remove typing indicator
                        $('.message.typing').remove();
                        if (response && response[0] && response[0].data) {
                            addMessage(response[0].data.response, 'assistant');
                        } else {
                            addMessage('Sorry, I could not process your request.', 'assistant');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Remove typing indicator
                        $('.message.typing').remove();
                        var errorMsg = 'Error: ' + error;
                        if (xhr.responseJSON && xhr.responseJSON[0] && xhr.responseJSON[0].error) {
                            errorMsg = 'Error: ' + xhr.responseJSON[0].message;
                        }
                        addMessage(errorMsg, 'assistant');
                    }
                });
            }

            /**
             * Add a message to chat display
             * @param {string} content - The message content
             * @param {string} type - The message type (user, assistant)
             */
            function addMessage(content, type) {
                var messagesContainer = $('#chat-messages');
                var avatar = type === 'user' ? '👤' : '🤖';
                
                // Format content for better display
                var formattedContent = formatMessageContent(content, type);
                
                var messageHtml = `
                    <div class="message ${type}">
                        <div class="message-avatar">${avatar}</div>
                        <div class="message-content">
                            <div class="message-text">${formattedContent}</div>
                            <div class="message-time">${new Date().toLocaleTimeString()}</div>
                        </div>
                    </div>
                `;
                messagesContainer.append(messageHtml);
                messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            }
            
            /**
             * Format message content for better display
             * @param {string} content - Raw content
             * @param {string} type - Message type
             * @return {string} Formatted content
             */
            function formatMessageContent(content, type) {
                if (type === 'user') {
                    return escapeHtml(content);
                }
                
                // Format assistant responses
                var formatted = escapeHtml(content);
                
                // Convert bullet points to styled lists
                formatted = formatted.replace(/\* (.+)$/gm, '<div class="bullet-item">• $1</div>');
                
                // Convert numbered lists
                formatted = formatted.replace(/^\d+\. (.+)$/gm, '<div class="number-item">• $1</div>');
                
                // Convert bold text
                formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                
                // Convert italic text
                formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
                
                // Convert code blocks
                formatted = formatted.replace(/```(.+?)```/gs, '<div class="code-block"><pre><code>$1</code></pre></div>');
                
                // Convert inline code
                formatted = formatted.replace(/`(.+?)`/g, '<code class="inline-code">$1</code>');
                
                // Convert URLs to links
                formatted = formatted.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="chat-link">$1</a>');
                
                // Convert key-value pairs (like "Total courses: 2")
                formatted = formatted.replace(/^([A-Za-z][^:]+):\s*(.+)$/gm, '<div class="stat-item"><span class="stat-label">$1:</span> <span class="stat-value">$2</span></div>');
                
                // Convert platform information
                formatted = formatted.replace(/(Platform Name|Platform URL|Platform Version|Site Name|Validation Hash|Data Source):\s*(.+)/g, 
                    '<div class="platform-info"><div class="info-label">$1:</div><div class="info-value">$2</div></div>');
                
                // Convert section headers
                formatted = formatted.replace(/^## (.+)$/gm, '<div class="section-header">$1</div>');
                formatted = formatted.replace(/^# (.+)$/gm, '<div class="main-header">$1</div>');
                
                // Convert multiple consecutive bullet items into a styled list
                formatted = formatted.replace(/(<div class="bullet-item">.*?<\/div>\s*)+/gs, function(match) {
                    return '<div class="styled-list">' + match + '</div>';
                });
                
                // Convert multiple consecutive stat items into a stats grid
                formatted = formatted.replace(/(<div class="stat-item">.*?<\/div>\s*)+/gs, function(match) {
                    return '<div class="stats-grid">' + match + '</div>';
                });
                
                // Convert multiple consecutive platform info items
                formatted = formatted.replace(/(<div class="platform-info">.*?<\/div>\s*)+/gs, function(match) {
                    return '<div class="platform-details">' + match + '</div>';
                });
                
                return formatted;
            }
            
            /**
             * Escape HTML to prevent XSS
             * @param {string} text - Text to escape
             * @return {string} Escaped text
             */
            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Toggle chat functionality
            $('.toggle-chat').click(function() {
                var messages = $('.chat-messages');
                var input = $('.chat-input');
                var button = $(this);

                if (messages.is(':visible')) {
                    messages.hide();
                    input.hide();
                    button.text('+');
                } else {
                    messages.show();
                    input.show();
                    button.text('−');
                }
            });
        }
    };
});