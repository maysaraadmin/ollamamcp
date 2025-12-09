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
                        <h4>AI Assistant</h4>
                        <button class="toggle-chat">−</button>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <div class="message assistant">
                            <div class="message-content">
                                Hello! I'm your AI assistant. How can I help you today?
                            </div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <textarea id="chat-input" placeholder="Type your message here..." rows="3"></textarea>
                        <button id="send-message" class="btn btn-primary">Send</button>
                    </div>
                </div>
            `;

            container.html(chatHtml);

            // Add basic styling
            var style = `
                <style>
                    .ollamamcp-chat {
                        border: 1px solid #ddd;
                        border-radius: 8px;
                        height: 500px;
                        display: flex;
                        flex-direction: column;
                        font-family: Arial, sans-serif;
                    }
                    .chat-header {
                        background: #f8f9fa;
                        padding: 10px 15px;
                        border-bottom: 1px solid #ddd;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    .chat-header h4 {
                        margin: 0;
                        color: #333;
                    }
                    .toggle-chat {
                        background: none;
                        border: none;
                        font-size: 18px;
                        cursor: pointer;
                        padding: 0;
                        width: 30px;
                        height: 30px;
                    }
                    .chat-messages {
                        flex: 1;
                        padding: 15px;
                        overflow-y: auto;
                        background: #fff;
                    }
                    .message {
                        margin-bottom: 15px;
                        display: flex;
                    }
                    .message.user {
                        justify-content: flex-end;
                    }
                    .message-content {
                        max-width: 70%;
                        padding: 10px 15px;
                        border-radius: 15px;
                        background: #f1f3f4;
                    }
                    .message.user .message-content {
                        background: #007bff;
                        color: white;
                    }
                    .message.assistant .message-content {
                        background: #e9ecef;
                    }
                    .chat-input {
                        padding: 15px;
                        border-top: 1px solid #ddd;
                        display: flex;
                        gap: 10px;
                    }
                    .chat-input textarea {
                        flex: 1;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                        padding: 10px;
                        resize: none;
                    }
                    .chat-input button {
                        align-self: flex-end;
                        padding: 10px 20px;
                        background: #007bff;
                        color: white;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                    }
                    .chat-input button:hover {
                        background: #0056b3;
                    }
                </style>
            `;

            $('head').append(style);

            // Handle message sending
            var self = this;
            $('#send-message').click(function() {
                sendMessage.call(self);
            });

            $('#chat-input').keypress(function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage.call(self);
                }
            });

            /**
             * Send a message to the AI assistant
             */
            function sendMessage() {
                var input = $('#chat-input');
                var message = input.val().trim();

                if (!message) {
                    return;
                }

                // Add user message
                addMessage(message, 'user');
                input.val('');

                // Show typing indicator
                addMessage('...', 'assistant typing');

                // Make real API call to Moodle
                $.ajax({
                    url: self.config.wwwroot + '/lib/ajax/service.php?method=local_ollamamcp_send_message',
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
             * Add a message to the chat display
             * @param {string} content - The message content
             * @param {string} type - The message type (user, assistant)
             */
            function addMessage(content, type) {
                var messagesContainer = $('#chat-messages');
                var messageHtml = `
                    <div class="message ${type}">
                        <div class="message-content">${content}</div>
                    </div>
                `;
                messagesContainer.append(messageHtml);
                messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
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