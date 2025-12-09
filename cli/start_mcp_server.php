#!/usr/bin/env php
<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

if (!get_config('local_ollamamcp', 'enabled')) {
    cli_error('Ollama MCP plugin is not enabled');
}

$longopts = [
    'model:',
    'port:',
    'host:',
    'help'
];

$options = cli_get_params($longopts, [
    'model' => get_config('local_ollamamcp', 'defaultmodel'),
    'port' => '8080',
    'host' => 'localhost',
    'help' => false
]);

if ($options['help']) {
    echo "Start MCP server for Ollama integration\n";
    echo "Options:\n";
    echo "--model=MODEL  Specify Ollama model (default: from settings)\n";
    echo "--port=PORT    Server port (default: 8080)\n";
    echo "--host=HOST    Server host (default: 127.0.0.1)\n";
    echo "--help         Show this help\n";
    exit(0);
}


// Load the MCP server class
require_once($CFG->dirroot . '/local/ollamamcp/classes/mcp/server.php');

// Create and start the server
$server = new local_ollamamcp_mcp_server($options['host'], $options['port'], $options['model']);

// Handle shutdown gracefully
function shutdown_handler($server) {
    $server->stop();
}

register_shutdown_function('shutdown_handler', $server);

// Start the server
$server->start();