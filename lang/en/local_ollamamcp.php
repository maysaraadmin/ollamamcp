<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Ollama MCP Integration';
$string['ollamaserver'] = 'Ollama Server URL';
$string['ollamaserver_desc'] = 'URL of the Ollama server (default: http://localhost:11434)';
$string['defaultmodel'] = 'Default Model';
$string['defaultmodel_desc'] = 'Default model to use for completions';
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'Enable Ollama MCP integration';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Optional API key for Ollama';
$string['timeout'] = 'Timeout';
$string['timeout_desc'] = 'Request timeout in seconds';
$string['contextlimit'] = 'Context Limit';
$string['contextlimit_desc'] = 'Maximum context length in tokens';
$string['curl_error'] = 'cURL Error';
$string['json_error'] = 'JSON Error';
$string['plugindisabled'] = 'Plugin is disabled';
$string['api_error'] = 'API Error';

// Web Service Configuration Strings
$string['webserviceheading'] = 'Web Service Configuration';
$string['webserviceheading_desc'] = 'Configure Moodle web services for Ollama MCP integration';
$string['enablewebservices'] = 'Enable Web Services';
$string['enablewebservices_desc'] = 'Enable Moodle web services and REST protocol for this plugin';
$string['webservicename'] = 'Web Service Name';
$string['webservicename_desc'] = 'Display name for the web service';
$string['webserviceshortname'] = 'Web Service Short Name';
$string['webserviceshortname_desc'] = 'Unique identifier for the web service (alphanumeric only)';
$string['autocreatewebservice'] = 'Auto-create Web Service';
$string['autocreatewebservice_desc'] = 'Automatically create the web service when settings are saved';
$string['createtokenforadmin'] = 'Create Admin Token';
$string['createtokenforadmin_desc'] = 'Automatically create a web service token for the admin user';
$string['error_invalid_shortname'] = 'Web service short name must contain only letters, numbers, and underscores';

// Token Management Strings
$string['tokenheading'] = 'Token Management';
$string['tokenheading_desc'] = 'View and manage web service tokens for API access';
$string['tokeninfo'] = 'Token Information';
$string['tokeninfo_desc'] = 'Current token details (read-only)';
$string['tokenvalue'] = 'Token Value';
$string['tokenvalue_desc'] = 'Current web service token (read-only)';
$string['notokenfound'] = 'No token found - enable web services and create token';
$string['regeneratetoken'] = 'Regenerate Token';
$string['regeneratetoken_desc'] = 'Generate a new web service token (this will invalidate the current token)';