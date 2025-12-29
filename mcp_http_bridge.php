<?php
// HTTP bridge for MCP server
require_once('../../config.php');

// Set up headers for CORS and JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate MCP request format
    if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
        throw new Exception('Invalid MCP request format');
    }
    
    $method = $data['method'] ?? '';
    $params = $data['params'] ?? [];
    $id = $data['id'] ?? null;
    
    // Route to appropriate MCP handler
    $response = handleMCPRequest($method, $params, $id);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => $data['id'] ?? null,
        'error' => [
            'code' => -32603,
            'message' => $e->getMessage()
        ]
    ]);
}

function handleMCPRequest($method, $params, $id) {
    global $CFG;
    
    switch ($method) {
        case 'initialize':
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
            
        case 'tools/list':
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
            
        case 'tools/call':
            return handleToolCall($params, $id);
            
        case 'completion/complete':
            return handleCompletion($params, $id);
            
        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found'
                ]
            ];
    }
}

function handleToolCall($params, $id) {
    $tool_name = $params['name'] ?? '';
    $arguments = $params['arguments'] ?? [];
    
    switch ($tool_name) {
        case 'ollama_chat':
            return callOllamaChat($arguments, $id);
            
        case 'moodle_info':
            return callMoodleInfo($arguments, $id);
            
        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Tool not found'
                ]
            ];
    }
}

function callOllamaChat($arguments, $id) {
    $prompt = $arguments['prompt'] ?? '';
    $context = $arguments['context'] ?? [];
    
    try {
        // Use the API class
        $api = new \local_ollamamcp\api();
        $response = $api->generate_completion($prompt);
        
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $response['response'] ?? 'No response from AI assistant.'
                    ]
                ]
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Ollama error: ' . $e->getMessage()
            ]
        ];
    }
}

function callMoodleInfo($arguments, $id) {
    global $CFG;
    
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
            // HTTP bridge runs in Moodle context, so we have user info
            global $USER;
            if (isset($USER->id) && $USER->id > 0) {
                $info = [
                    'id' => $USER->id,
                    'username' => $USER->username,
                    'email' => $USER->email,
                    'context' => 'web_interface'
                ];
            } else {
                $info = [
                    'id' => 0,
                    'username' => 'guest',
                    'email' => 'guest@localhost',
                    'context' => 'web_interface_guest'
                ];
            }
            break;
            
        case 'general':
        default:
            $info = [
                'moodle_version' => $CFG->version,
                'wwwroot' => $CFG->wwwroot,
                'plugin_version' => get_config('local_ollamamcp', 'version') ?: '0.1.0',
                'context' => 'web_interface'
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

function handleCompletion($params, $id) {
    $prompt = $params['prompt'] ?? '';
    
    try {
        $api = new \local_ollamamcp\api();
        $response = $api->generate_completion($prompt);
        
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => [
                'completion' => [
                    'text' => $response['response'] ?? 'No response available.'
                ]
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => -32603,
                'message' => 'Completion error: ' . $e->getMessage()
            ]
        ];
    }
}
?>
