<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/settingslib.php');
require_once(__DIR__.'/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ollamamcp', get_string('pluginname', 'local_ollamamcp'));
    
    $ADMIN->add('localplugins', $settings);
    
    $settings->add(new admin_setting_configcheckbox('local_ollamamcp/enabled',
        get_string('enabled', 'local_ollamamcp'),
        get_string('enabled_desc', 'local_ollamamcp'), 0));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/ollamaserver',
        get_string('ollamaserver', 'local_ollamamcp'),
        get_string('ollamaserver_desc', 'local_ollamamcp'),
        'http://localhost:11434', PARAM_URL));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/defaultmodel',
        get_string('defaultmodel', 'local_ollamamcp'),
        get_string('defaultmodel_desc', 'local_ollamamcp'),
        'llama3.2:latest', PARAM_TEXT));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/apikey',
        get_string('apikey', 'local_ollamamcp'),
        get_string('apikey_desc', 'local_ollamamcp'),
        '', PARAM_TEXT));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/timeout',
        get_string('timeout', 'local_ollamamcp'),
        get_string('timeout_desc', 'local_ollamamcp'),
        '60', PARAM_INT));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/contextlimit',
        get_string('contextlimit', 'local_ollamamcp'),
        get_string('contextlimit_desc', 'local_ollamamcp'),
        '4096', PARAM_INT));
    
    // Documentation Search Settings
    $settings->add(new admin_setting_heading('local_ollamamcp/docsearchheading',
        get_string('docsearchheading', 'local_ollamamcp'),
        get_string('docsearchheading_desc', 'local_ollamamcp')));
    
    $settings->add(new admin_setting_configcheckbox('local_ollamamcp/enable_docsearch',
        get_string('enable_docsearch', 'local_ollamamcp'),
        get_string('enable_docsearch_desc', 'local_ollamamcp'), 1));
    
    $settings->add(new admin_setting_configtext('local_ollamamcp/docsearch_limit',
        get_string('docsearch_limit', 'local_ollamamcp'),
        get_string('docsearch_limit_desc', 'local_ollamamcp'),
        '3', PARAM_INT));
    
    // Web Service Configuration
    $settings->add(new admin_setting_heading('local_ollamamcp/webserviceheading',
        get_string('webserviceheading', 'local_ollamamcp'),
        get_string('webserviceheading_desc', 'local_ollamamcp')));
    
    $settings->add(new local_ollamamcp_admin_setting_webservice('local_ollamamcp/enablewebservices',
        get_string('enablewebservices', 'local_ollamamcp'),
        get_string('enablewebservices_desc', 'local_ollamamcp'), 0));
    
    $settings->add(new local_ollamamcp_admin_setting_servicename('local_ollamamcp/webservicename',
        get_string('webservicename', 'local_ollamamcp'),
        get_string('webservicename_desc', 'local_ollamamcp'),
        'Ollama MCP Service', PARAM_TEXT));
    
    $settings->add(new local_ollamamcp_admin_setting_serviceshortname('local_ollamamcp/webserviceshortname',
        get_string('webserviceshortname', 'local_ollamamcp'),
        get_string('webserviceshortname_desc', 'local_ollamamcp'),
        'ollamamcp', PARAM_ALPHANUMEXT));
    
    // Token Management Section
    $settings->add(new admin_setting_heading('local_ollamamcp/tokenheading',
        get_string('tokenheading', 'local_ollamamcp'),
        get_string('tokenheading_desc', 'local_ollamamcp')));
    
    $token_info = null;
    if (function_exists('local_ollamamcp_get_token_info')) {
        $token_info = local_ollamamcp_get_token_info();
    }
    
    if ($token_info) {
        $token_display = "Service: {$token_info['servicename']}, Created: {$token_info['created']}";
        $settings->add(new admin_setting_configtext('local_ollamamcp/tokeninfo',
            get_string('tokeninfo', 'local_ollamamcp'),
            get_string('tokeninfo_desc', 'local_ollamamcp'),
            $token_display, PARAM_TEXT));
        $settings->add(new admin_setting_configtext('local_ollamamcp/tokenvalue',
            get_string('tokenvalue', 'local_ollamamcp'),
            get_string('tokenvalue_desc', 'local_ollamamcp'),
            $token_info['token'], PARAM_TEXT));
    } else {
        $settings->add(new admin_setting_configtext('local_ollamamcp/tokeninfo',
            get_string('tokeninfo', 'local_ollamamcp'),
            get_string('tokeninfo_desc', 'local_ollamamcp'),
            get_string('notokenfound', 'local_ollamamcp'), PARAM_TEXT));
        $settings->add(new admin_setting_configtext('local_ollamamcp/tokenvalue',
            get_string('tokenvalue', 'local_ollamamcp'),
            get_string('tokenvalue_desc', 'local_ollamamcp'),
            '', PARAM_TEXT));
    }
    



}
