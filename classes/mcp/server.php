<?php
namespace local_ollamamcp\mcp;

defined('MOODLE_INTERNAL') || die();

/**
 * MCP Server class for Ollama integration
 * 
 * This class implements a Model Context Protocol server that bridges
 * Moodle with Ollama for AI-powered functionality.
 */
class server {
    
    /** @var string Server host */
    private $host;
    
    /** @var int Server port */
    private $port;
    
    /** @var string Ollama model */
    private $model;
    
    /** @var string Ollama server URL */
    private $ollama_url;
    
    /** @var int Request timeout */
    private $timeout;
    
    /** @var int Context limit */
    private $context_limit;
    
    /** @var string API key */
    private $api_key;
    
    /** @var resource Server socket */
    private $socket;
    
    /** @var bool Server running status */
    private $running = false;
    
    /**
     * Constructor
     * 
     * @param string $host Server host
     * @param int $port Server port
     * @param string $model Ollama model
     */
    public function __construct($host = 'localhost', $port = 8080, $model = null) {
        $this->host = $host;
        $this->port = $port;
        $this->model = $model ?: get_config('local_ollamamcp', 'defaultmodel');
        $this->ollama_url = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
        $this->timeout = get_config('local_ollamamcp', 'timeout') ?: 30;
        $this->context_limit = get_config('local_ollamamcp', 'contextlimit') ?: 4096;
        $this->api_key = get_config('local_ollamamcp', 'apikey');
    }
    
    /**
     * Start the MCP server
     * 
     * @return bool True on success
     */
    public function start() {
        try {
            // Create socket
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$this->socket) {
                throw new Exception('Failed to create socket: ' . socket_strerror(socket_last_error()));
            }
            
            // Set socket options
            socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
            
            // Bind socket
            if (!socket_bind($this->socket, $this->host, $this->port)) {
                throw new Exception('Failed to bind socket: ' . socket_strerror(socket_last_error($this->socket)));
            }
            
            // Start listening
            if (!socket_listen($this->socket, 5)) {
                throw new Exception('Failed to listen on socket: ' . socket_strerror(socket_last_error($this->socket)));
            }
            
            $this->running = true;
            
            echo "MCP Server started on {$this->host}:{$this->port}\n";
            echo "Using Ollama model: {$this->model}\n";
            echo "Ollama server: {$this->ollama_url}\n";
            
            // Main server loop
            while ($this->running) {
                $client = socket_accept($this->socket);
                if ($client) {
                    $this->handle_client($client);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "Server error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Stop the MCP server
     */
    public function stop() {
        $this->running = false;
        if ($this->socket) {
            socket_close($this->socket);
        }
        echo "MCP Server stopped\n";
    }
    
    /**
     * Handle client connection
     * 
     * @param resource $client Client socket
     */
    private function handle_client($client) {
        try {
            // Read request data with proper buffering
            $request = '';
            while (true) {
                $chunk = socket_read($client, 4096, PHP_NORMAL_READ);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $request .= $chunk;
                // Check if we have a complete request (ends with double newline)
                if (strpos($request, "\r\n\r\n") !== false || strpos($request, "\n\n") !== false) {
                    break;
                }
            }
            
            if (empty($request)) {
                socket_close($client);
                return;
            }
            
            // Check if this is an HTTP request (simple health check)
            if (strpos($request, 'GET /') === 0 || strpos($request, 'HEAD /') === 0) {
                // Simple HTTP response for health check
                $http_response = "HTTP/1.1 200 OK\r\n";
                $http_response .= "Content-Type: text/plain\r\n";
                $http_response .= "Content-Length: 2\r\n";
                $http_response .= "\r\n";
                $http_response .= "OK";
                socket_write($client, $http_response);
                socket_close($client);
                return;
            }
            
            // Trim whitespace and parse JSON-RPC request
            $request = trim($request);
            $data = json_decode($request, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->send_error($client, -32700, 'Parse error: ' . json_last_error_msg());
                socket_close($client);
                return;
            }
            
            // Validate JSON-RPC structure
            if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
                $this->send_error($client, -32600, 'Invalid Request');
                socket_close($client);
                return;
            }
            
            // Process request
            $response = $this->process_request($data);
            
            // Send response
            $response_json = json_encode($response);
            if ($response_json === false) {
                $this->send_error($client, -32603, 'Internal error: Failed to encode response');
            } else {
                socket_write($client, $response_json . "\n");
            }
            
        } catch (Exception $e) {
            $this->send_error($client, -32603, 'Internal error: ' . $e->getMessage());
        } finally {
            if ($client && is_resource($client)) {
                socket_close($client);
            }
        }
    }
    
    /**
     * Process MCP request
     * 
     * @param array $data Request data
     * @return array Response
     */
    private function process_request($data) {
        $method = $data['method'] ?? '';
        $params = $data['params'] ?? [];
        $id = $data['id'] ?? null;
        
        switch ($method) {
            case 'initialize':
                return $this->handle_initialize($params, $id);
                
            case 'tools/list':
                return $this->handle_tools_list($params, $id);
                
            case 'tools/call':
                return $this->handle_tools_call($params, $id);
                
            case 'completion/complete':
                return $this->handle_completion($params, $id);
                
            default:
                return $this->create_error_response($id, -32601, 'Method not found');
        }
    }
    
    /**
     * Handle initialize request
     * 
     * @param array $params Request parameters
     * @param mixed $id Request ID
     * @return array Response
     */
    private function handle_initialize($params, $id) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => [
                        'listChanged' => false
                    ],
                    'completion' => [
                        'listChanged' => false
                    ]
                ],
                'serverInfo' => [
                    'name' => 'Ollama MCP Server',
                    'version' => '1.0.0'
                ]
            ]
        ];
    }
    
    /**
     * Handle tools list request
     * 
     * @param array $params Request parameters
     * @param mixed $id Request ID
     * @return array Response
     */
    private function handle_tools_list($params, $id) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'tools' => [
                    [
                        'name' => 'ollama_chat',
                        'description' => 'Chat with Ollama model',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'prompt' => [
                                    'type' => 'string',
                                    'description' => 'The prompt to send to Ollama'
                                ],
                                'context' => [
                                    'type' => 'array',
                                    'description' => 'Previous conversation context',
                                    'items' => ['type' => 'string']
                                ]
                            ],
                            'required' => ['prompt']
                        ]
                    ],
                    [
                        'name' => 'moodle_info',
                        'description' => 'Get Moodle information',
                        'inputSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'type' => [
                                    'type' => 'string',
                                    'description' => 'Type of information (course, user, etc.)'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Handle tools call request
     * 
     * @param array $params Request parameters
     * @param mixed $id Request ID
     * @return array Response
     */
    private function handle_tools_call($params, $id) {
        $tool_name = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        
        switch ($tool_name) {
            case 'ollama_chat':
                return $this->call_ollama_chat($arguments, $id);
                
            case 'moodle_info':
                return $this->call_moodle_info($arguments, $id);
                
            default:
                return $this->create_error_response($id, -32601, 'Tool not found');
        }
    }
    
    /**
     * Call Ollama chat
     * 
     * @param array $arguments Tool arguments
     * @param mixed $id Request ID
     * @return array Response
     */
    private function call_ollama_chat($arguments, $id) {
        $prompt = $arguments['prompt'] ?? '';
        $context = $arguments['context'] ?? [];
        
        try {
            $response = $this->send_ollama_request($prompt, $context);
            
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $response
                        ]
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return $this->create_error_response($id, -32603, 'Ollama error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Moodle information
     * 
     * @param array $arguments Tool arguments
     * @param mixed $id Request ID
     * @return array Response
     */
    private function call_moodle_info($arguments, $id) {
        global $CFG, $USER;
        
        $type = $arguments['type'] ?? 'general';
        $info = [];
        
        switch ($type) {
            case 'course':
                if (isset($arguments['courseid'])) {
                    $course = get_course($arguments['courseid']);
                    $info = [
                        'fullname' => $course->fullname,
                        'shortname' => $course->shortname,
                        'summary' => $course->summary
                    ];
                }
                break;
                
            case 'user':
                $info = [
                    'id' => $USER->id,
                    'username' => $USER->username,
                    'email' => $USER->email
                ];
                break;
                
            case 'general':
            default:
                $info = [
                    'moodle_version' => $CFG->version,
                    'wwwroot' => $CFG->wwwroot,
                    'plugin_version' => get_config('local_ollamamcp', 'version') ?: '0.1.0'
                ];
                break;
        }
        
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($info, JSON_PRETTY_PRINT)
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Handle completion request
     * 
     * @param array $params Request parameters
     * @param mixed $id Request ID
     * @return array Response
     */
    private function handle_completion($params, $id) {
        $prompt = $params['prompt'] ?? '';
        
        try {
            $response = $this->send_ollama_request($prompt);
            
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'completion' => [
                        'text' => $response
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return $this->create_error_response($id, -32603, 'Completion error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send request to Ollama
     * 
     * @param string $prompt The prompt
     * @param array $context Conversation context
     * @return string Response text
     */
    private function send_ollama_request($prompt, $context = []) {
        if (empty($this->ollama_url)) {
            throw new Exception('Ollama server URL is not configured');
        }
        
        if (empty($this->model)) {
            throw new Exception('Ollama model is not specified');
        }
        
        $url = rtrim($this->ollama_url, '/') . '/api/generate';
        
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => $this->context_limit,
                'temperature' => 0.7
            ]
        ];
        
        if (!empty($context)) {
            $data['context'] = $context;
        }
        
        $json_data = json_encode($data);
        if ($json_data === false) {
            throw new Exception('Failed to encode request data: ' . json_last_error_msg());
        }
        
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception('Failed to initialize cURL');
        }
        
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json_data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => array_merge(
                    ['Content-Type: application/json'],
                    $this->api_key ? ['Authorization: Bearer ' . $this->api_key] : []
                ),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $response = curl_exec($ch);
            
            if ($response === false) {
                throw new Exception('cURL error: ' . curl_error($ch));
            }
            
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code !== 200) {
                $error_info = '';
                if ($response) {
                    $error_response = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($error_response['error'])) {
                        $error_info = ': ' . (is_string($error_response['error']) ? $error_response['error'] : json_encode($error_response['error']));
                    }
                }
                throw new Exception('HTTP error ' . $http_code . $error_info);
            }
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from Ollama: ' . json_last_error_msg());
            }
            
            if (!isset($result['response'])) {
                throw new Exception('Invalid response format from Ollama: missing response field');
            }
            
            return $result['response'];
            
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
    }
    
    /**
     * Send error
     * 
     * @param resource $client Client socket
     * @param int $code Error code
     * @param string $message Error message
     */
    private function send_error($client, $code, $message) {
        $response = $this->create_error_response(null, $code, $message);
        $response_json = json_encode($response);
        if ($response_json !== false) {
            socket_write($client, $response_json . "\n");
        }
    }
    
    /**
     * Create error response
     * 
     * @param mixed $id Request ID
     * @param int $code Error code
     * @param string $message Error message
     * @return array Error response
     */
    private function create_error_response($id, $code, $message) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
    
    /**
     * Check if JSON string is complete
     * 
     * @param string $json JSON string to check
     * @return bool True if JSON is complete
     */
    private function is_complete_json($json) {
        $trimmed = trim($json);
        if (empty($trimmed)) {
            return false;
        }
        
        // Simple check for balanced braces
        $open_count = substr_count($trimmed, '{');
        $close_count = substr_count($trimmed, '}');
        
        return $open_count === $close_count && $open_count > 0;
    }
}