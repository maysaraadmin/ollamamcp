<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for Ollama MCP plugin
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_ollamamcp_upgrade($oldversion) {
    global $DB;
    
    $result = true;
    
    // Add installation time if not exists
    if ($oldversion < 2025122901) {
        set_config('local_ollamamcp', 'installation_time', time());
    }
    
    // Save plugin version
    set_config('local_ollamamcp', 'version', 2025122901);
    
    return $result;
}
