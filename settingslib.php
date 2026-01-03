<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Custom admin setting class for web service configuration
 */
class local_ollamamcp_admin_setting_webservice extends admin_setting_configcheckbox {
    
    public function write_setting($data) {
        $result = parent::write_setting($data);
        
        // If checkbox is checked and auto-create is enabled, setup web service
        if ($data && get_config('local_ollamamcp', 'autocreatewebservice')) {
            local_ollamamcp_setup_webservice(true);
        }
        
        return $result;
    }
}

/**
 * Custom admin setting class for auto-create web service checkbox
 */
class local_ollamamcp_admin_setting_autocreate extends admin_setting_configcheckbox {
    
    public function write_setting($data) {
        $result = parent::write_setting($data);
        
        // If auto-create is enabled and web services are enabled, setup web service
        if ($data && get_config('local_ollamamcp', 'enablewebservices')) {
            local_ollamamcp_setup_webservice(true);
        }
        
        return $result;
    }
}

/**
 * Custom admin setting class for web service name
 */
class local_ollamamcp_admin_setting_servicename extends admin_setting_configtext {
    
    public function write_setting($data) {
        $result = parent::write_setting($data);
        
        // If web services are enabled and auto-create is enabled, update web service
        if (get_config('local_ollamamcp', 'enablewebservices') && 
            get_config('local_ollamamcp', 'autocreatewebservice')) {
            local_ollamamcp_setup_webservice(true);
        }
        
        return $result;
    }
}

/**
 * Custom admin setting class for web service short name
 */
class local_ollamamcp_admin_setting_serviceshortname extends admin_setting_configtext {
    
    public function write_setting($data) {
        // Validate short name format
        if (!empty($data) && !preg_match('/^[a-zA-Z0-9_]+$/', $data)) {
            return get_string('error_invalid_shortname', 'local_ollamamcp');
        }
        
        $result = parent::write_setting($data);
        
        // If web services are enabled and auto-create is enabled, update web service
        if (get_config('local_ollamamcp', 'enablewebservices') && 
            get_config('local_ollamamcp', 'autocreatewebservice')) {
            local_ollamamcp_setup_webservice(true);
        }
        
        return $result;
    }
}

/**
 * Custom admin setting class for token regeneration
 */
class local_ollamamcp_admin_setting_regeneratetoken extends admin_setting_configcheckbox {
    
    public function write_setting($data) {
        $result = parent::write_setting($data);
        
        // If checkbox is checked, regenerate token
        if ($data) {
            if (local_ollamamcp_regenerate_token()) {
                // Reset the checkbox to unchecked after regeneration
                set_config('regeneratetoken', 0, 'local_ollamamcp');
            }
        }
        
        return $result;
    }
}
