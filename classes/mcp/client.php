<?php
namespace local_ollamamcp\mcp;

defined('MOODLE_INTERNAL') || die();

class client {
    private $serverurl;
    private $apikey;
    private $timeout;
    
    public function __construct() {
        $this->serverurl = get_config('local_ollamamcp', 'ollamaserver');
        $this->apikey = get_config('local_ollamamcp', 'apikey');
        $this->timeout = get_config('local_ollamamcp', 'timeout');
    }
    
    public function generate_completion($prompt, $model = null, $options = []) {
        if (!$model) {
            $model = get_config('local_ollamamcp', 'defaultmodel');
        }
        
        $url = $this->serverurl . '/api/generate';
        
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false
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
            'stream' => false
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
        ]);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            throw new \moodle_exception('curl_error', 'local_ollamamcp', '', $error);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('json_error', 'local_ollamamcp', '', json_last_error_msg());
        }
        
        return $decoded;
    }
}