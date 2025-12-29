#!/usr/bin/env php
<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

if (!get_config('local_ollamamcp', 'enabled')) {
    cli_error('Ollama MCP plugin is not enabled');
}

// Manual parameter parsing as fallback
$model = get_config('local_ollamamcp', 'defaultmodel');
$port = '8080';
$host = 'localhost';

foreach ($argv as $arg) {
    if (strpos($arg, '--model=') === 0) {
        $model = substr($arg, 8);
    } elseif (strpos($arg, '--port=') === 0) {
        $port = substr($arg, 7);
    } elseif (strpos($arg, '--host=') === 0) {
        $host = substr($arg, 7);
    } elseif ($arg === '--help') {
        echo "Start MCP server for Ollama integration\n";
        echo "Options:\n";
        echo "--model=MODEL  Specify Ollama model (default: from settings)\n";
        echo "--port=PORT    Server port (default: 8080)\n";
        echo "--host=HOST    Server host (default: localhost)\n";
        echo "--help         Show this help\n";
        exit(0);
    }
}

$longopts = [
    'model:',
    'port:',
    'host:',
    'help'
];

$options = cli_get_params($longopts, [
    'model' => $model,
    'port' => $port,
    'host' => $host,
    'help' => false
]);

// Use manual parsing if cli_get_params fails
if (empty($options['model']) && !empty($model)) {
    $options['model'] = $model;
}
if (empty($options['port']) && !empty($port)) {
    $options['port'] = $port;
}
if (empty($options['host']) && !empty($host)) {
    $options['host'] = $host;
}

if ($options['help']) {
    echo "Start MCP server for Ollama integration\n";
    echo "Options:\n";
    echo "--model=MODEL  Specify Ollama model (default: from settings)\n";
    echo "--port=PORT    Server port (default: 8080)\n";
    echo "--host=HOST    Server host (default: localhost)\n";
    echo "--help         Show this help\n";
    exit(0);
}

// Load the MCP server class
require_once($CFG->dirroot . '/local/ollamamcp/classes/mcp/server.php');

// Create and start the server
$server = new \local_ollamamcp\mcp\server($options['host'], $options['port'], $options['model']);

// Handle shutdown gracefully
function shutdown_handler($server) {
    $server->stop();
}

register_shutdown_function('shutdown_handler', $server);

// Start the server
$server->start();