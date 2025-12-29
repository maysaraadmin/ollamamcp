<?php
defined('MOODLE_INTERNAL') || die();

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
}