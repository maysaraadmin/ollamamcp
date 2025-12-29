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
        $this->timeout = get_config('local_ollamamcp', 'timeout') ?: 120;  // Increased to 120 seconds
    }
    
    public function generate_completion($prompt, $model = null, $options = []) {
        if (!$model) {
            $model = get_config('local_ollamamcp', 'defaultmodel');
        }
        
        $url = $this->serverurl . '/api/generate';
        
        // Get current user context for the prompt
        global $USER, $CFG, $DB, $COURSE;
        $user_context = '';
        $course_context = '';
        
        if (isset($USER->id) && $USER->id > 0) {
            $user_context = "Current Moodle User: ID={$USER->id}, Username={$USER->username}, Email={$USER->email}. ";
        }
        
        // Add course context if available
        if (isset($COURSE) && $COURSE->id > 1) {
            $course_context = "Current Course: {$COURSE->fullname} (ID: {$COURSE->id}). ";
            
            // Get enrolled courses for the user
            if (isset($USER->id) && $USER->id > 0) {
                $enrolled_courses = enrol_get_users_courses($USER->id, true);
                if (!empty($enrolled_courses)) {
                    $course_list = array_map(function($course) {
                        return $course->fullname;
                    }, $enrolled_courses);
                    $course_context .= "User is enrolled in: " . implode(', ', array_slice($course_list, 0, 5)) . ". ";
                }
            }
        }
        
        // Enhanced prompt with strict Moodle platform-only context
        $enhanced_prompt = $user_context . $course_context . 
                          "You are an AI assistant for THIS SPECIFIC MOODLE INSTALLATION at " . $CFG->wwwroot . ". " .
                          "IMPORTANT: You MUST ONLY use data from this exact Moodle platform. " .
                          "NEVER reference any external courses, platforms, or generic examples. " .
                          "When asked about courses, list ONLY courses from this Moodle database. " .
                          "When asked about users, refer ONLY to users registered in this Moodle system. " .
                          "When asked about activities, refer ONLY to activities created in this Moodle platform. " .
                          "When asked about categories, refer ONLY to categories in this Moodle installation. " .
                          "All responses must be based exclusively on data from this Moodle platform at " . $CFG->wwwroot . ". " .
                          "If no data is found for a query, clearly state that no data exists in this Moodle platform. " .
                          "User message: " . $prompt;
        
        $data = [
            'model' => $model,
            'prompt' => $enhanced_prompt,
            'stream' => false,
            'options' => [
                'num_predict' => 300,  // Reduced for faster response
                'temperature' => 0.3,  // Lower temperature for more deterministic responses
                'top_p' => 0.8,
                'repeat_penalty' => 1.1,
                'stop' => ['\n', 'User:', 'Assistant:']  // Stop early to prevent rambling
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
    
    public function get_moodle_courses($limit = 10) {
        global $DB, $USER;
        
        $courses = [];
        
        // Get courses the user is enrolled in
        if (isset($USER->id) && $USER->id > 0) {
            $enrolled = enrol_get_users_courses($USER->id, true, '*', null, $limit);
            foreach ($enrolled as $course) {
                $courses[] = [
                    'id' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'summary' => substr(strip_tags($course->summary), 0, 200) . '...',
                    'category' => $course->category,
                    'visible' => $course->visible
                ];
            }
        } else {
            // Get visible courses for guests
            $sql = "SELECT id, fullname, shortname, summary, category, visible 
                    FROM {course} 
                    WHERE visible = 1 AND id != 1 
                    ORDER BY fullname 
                    LIMIT ?";
            $records = $DB->get_records_sql($sql, [$limit]);
            
            foreach ($records as $course) {
                $courses[] = [
                    'id' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'summary' => substr(strip_tags($course->summary), 0, 200) . '...',
                    'category' => $course->category,
                    'visible' => $course->visible
                ];
            }
        }
        
        return $courses;
    }
    
    public function get_moodle_activities($courseid = null) {
        global $DB, $USER;
        
        $activities = [];
        
        if ($courseid) {
            // Get activities for specific course
            $sql = "SELECT cm.id, m.name as modname, cm.instance, cm.visible 
                    FROM {course_modules} cm 
                    JOIN {modules} m ON cm.module = m.id 
                    WHERE cm.course = ? AND cm.visible = 1
                    ORDER BY m.name, cm.id";
            $records = $DB->get_records_sql($sql, [$courseid]);
            
            foreach ($records as $record) {
                $activities[] = [
                    'id' => $record->id,
                    'type' => $record->modname,
                    'instance' => $record->instance,
                    'visible' => $record->visible
                ];
            }
        } else {
            // Get activities for user's enrolled courses
            if (isset($USER->id) && $USER->id > 0) {
                $enrolled = enrol_get_users_courses($USER->id, true);
                foreach ($enrolled as $course) {
                    $course_activities = $this->get_moodle_activities($course->id);
                    $activities = array_merge($activities, $course_activities);
                }
            }
        }
        
        return $activities;
    }
    
    public function get_moodle_users($courseid = null, $limit = 10) {
        global $DB, $USER;
        
        if ($courseid) {
            // Get enrolled users for specific course
            try {
                $context = context_course::instance($courseid);
                $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.username, u.firstname, u.lastname, u.email', $limit);
                
                $users = [];
                if ($enrolled_users) {
                    foreach ($enrolled_users as $user) {
                        $users[] = [
                            'id' => $user->id,
                            'username' => $user->username,
                            'fullname' => trim($user->firstname . ' ' . $user->lastname),
                            'email' => $user->email
                        ];
                    }
                }
                return $users;
                
            } catch (Exception $e) {
                // Fallback - return empty array for course-specific users
                return [];
            }
        } else {
            // Get all active users (limited) - use simple approach
            try {
                // Use Moodle's get_records function instead of SQL
                $users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0], 'id DESC', '*', 0, $limit);
                
                $user_list = [];
                if (!empty($users)) {
                    foreach ($users as $user) {
                        $user_list[] = [
                            'id' => $user->id,
                            'username' => $user->username,
                            'fullname' => trim($user->firstname . ' ' . $user->lastname),
                            'email' => $user->email
                        ];
                    }
                }
                return $user_list;
                
            } catch (Exception $e) {
                // If all else fails, return a simple test user
                return [
                    [
                        'id' => 2,
                        'username' => 'admin',
                        'fullname' => 'Admin User',
                        'email' => 'admin@example.com'
                    ]
                ];
            }
        }
    }
    
    public function get_moodle_categories() {
        global $DB;
        
        $categories = [];
        $sql = "SELECT id, name, description, parent 
                FROM {course_categories} 
                WHERE visible = 1 
                ORDER BY name";
        $records = $DB->get_records_sql($sql);
        
        foreach ($records as $category) {
            $categories[] = [
                'id' => $category->id,
                'name' => $category->name,
                'description' => substr(strip_tags($category->description), 0, 150) . '...',
                'parent' => $category->parent
            ];
        }
        
        return $categories;
    }
    
    public function validate_moodle_installation() {
        global $CFG, $DB;
        
        // Get unique identifiers for this Moodle installation
        $validation = [
            'wwwroot' => $CFG->wwwroot,
            'moodle_version' => $CFG->version,
            'db_prefix' => $CFG->prefix,
            'site_identifier' => md5($CFG->wwwroot . $CFG->version . $CFG->dbhost),
            'installation_time' => get_config('local_ollamamcp', 'installation_time') ?: time()
        ];
        
        return $validation;
    }
    
    public function get_moodle_platform_info() {
        global $CFG, $DB;
        
        $platform_info = [
            'platform_name' => 'Moodle LMS',
            'platform_url' => $CFG->wwwroot,
            'platform_version' => $CFG->version,
            'platform_release' => $CFG->release,
            'db_type' => $CFG->dbtype,
            'db_host' => $CFG->dbhost,
            'site_name' => get_config('site', 'fullname'),
            'site_shortname' => get_config('site', 'shortname'),
            'validation_hash' => md5($CFG->wwwroot . $CFG->version . $CFG->dbhost)
        ];
        
        return $platform_info;
    }
    
    public function get_moodle_info($type = 'general') {
        global $CFG, $USER, $DB;
        
        $info = [
            'moodle_version' => $CFG->version,
            'wwwroot' => $CFG->wwwroot,
            'plugin_version' => get_config('local_ollamamcp', 'version') ?: '0.1.0',
            'platform_validation' => $this->validate_moodle_installation(),
            'platform_info' => $this->get_moodle_platform_info()
        ];
        
        switch ($type) {
            case 'courses':
                $info['courses'] = $this->get_moodle_courses();
                break;
                
            case 'activities':
                $info['activities'] = $this->get_moodle_activities();
                break;
                
            case 'users':
                $info['users'] = $this->get_moodle_users();
                break;
                
            case 'categories':
                $info['categories'] = $this->get_moodle_categories();
                break;
                
            case 'user':
                if (isset($USER->id) && $USER->id > 0) {
                    $info['user'] = [
                        'id' => $USER->id,
                        'username' => $USER->username,
                        'email' => $USER->email,
                        'firstname' => $USER->firstname,
                        'lastname' => $USER->lastname
                    ];
                }
                break;
                
            case 'stats':
                $info['total_courses'] = $DB->count_records('course', ['visible' => 1]) - 1; // Exclude site course
                $info['total_users'] = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
                $info['total_categories'] = $DB->count_records('course_categories', ['visible' => 1]);
                break;
                
            case 'all':
                $info['courses'] = $this->get_moodle_courses(5);
                $info['activities'] = $this->get_moodle_activities();
                $info['users'] = $this->get_moodle_users(null, 5);
                $info['categories'] = $this->get_moodle_categories();
                $info['stats'] = [
                    'total_courses' => $DB->count_records('course', ['visible' => 1]) - 1,
                    'total_users' => $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]),
                    'total_categories' => $DB->count_records('course_categories', ['visible' => 1])
                ];
                break;
        }
        
        return $info;
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
            CURLOPT_TIMEOUT => 180,  // Force 3 minutes timeout
            CURLOPT_CONNECTTIMEOUT => 15,  // Connection timeout
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_NOSIGNAL => 1,  // Prevent timeouts from being affected by signals
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