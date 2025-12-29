<?php
namespace local_ollamamcp\mcp;

defined('MOODLE_INTERNAL') || die();

class client {
    private $serverurl;
    private $apikey;
    private $timeout;
    
    public function __construct() {
        $this->serverurl = get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434';
        $this->apikey = get_config('local_ollamamcp', 'apikey');
        $this->timeout = get_config('local_ollamamcp', 'timeout') ?: 60;
    }
    
    public function generate_completion($prompt, $model = null, $options = []) {
        if (!$model) {
            $model = get_config('local_ollamamcp', 'defaultmodel');
        }
        
        $url = $this->serverurl . '/api/generate';
        
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => 500,  // Limit response length for faster response
                'temperature' => 0.7,
                'top_p' => 0.9
            ]
        ];
        
        if (!empty($options)) {
            $data = array_merge($data, $options);
        }
        
        return $this->make_request($url, $data);
    }
    
    public function chat_completion($messages, $model = null, $options = []) {
        if (!$model) {
            $model = get_config('local_ollamamcp', 'defaultmodel');
        }
        
        $url = $this->serverurl . '/api/chat';
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'num_predict' => 500,  // Limit response length
                'temperature' => 0.7,
                'top_p' => 0.9
            ]
        ];
        
        if (!empty($options)) {
            $data = array_merge($data, $options);
        }
        
        return $this->make_request($url, $data);
    }
    
    public function list_models() {
        $url = $this->serverurl . '/api/tags';
        return $this->make_request($url, [], 'GET');
    }
    
    public function get_model_info($model) {
        $url = $this->serverurl . '/api/show';
        return $this->make_request($url, ['name' => $model]);
    }
    
    private function make_request($url, $data = [], $method = 'POST') {
        $curl = curl_init();
        
        $headers = [
            'Content-Type: application/json',
        ];
        
        if ($this->apikey) {
            $headers[] = 'Authorization: Bearer ' . $this->apikey;
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($error) {
            throw new \Exception("cURL Error (HTTP $http_code): $error");
        }
        
        if ($http_code !== 200) {
            throw new \Exception("HTTP Error: $http_code - Response: " . substr($response, 0, 200));
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON Error: ' . json_last_error_msg() . ' - Response: ' . substr($response, 0, 200));
        }
        
        return $decoded;
    }
}