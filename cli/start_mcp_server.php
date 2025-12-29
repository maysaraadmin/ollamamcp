#!/usr/bin/env php
<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Check if plugin is enabled
if (!get_config('local_ollamamcp', 'enabled')) {
    cli_error('Ollama MCP plugin is not enabled');
}

// Parse command line arguments
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'host' => 'localhost',
        'port' => '8080',
        'model' => get_config('local_ollamamcp', 'defaultmodel') ?: 'llama3.2:latest',
        'verbose' => false
    ),
    array(
        'h' => 'help',
        'H' => 'host',
        'p' => 'port',
        'm' => 'model',
        'v' => 'verbose'
    )
);

if ($options['help']) {
    $help = "
Start MCP server for Ollama integration

Options:
-h, --help          Print out this help
-H, --host          Server host (default: localhost)
-p, --port          Server port (default: 8080)
-m, --model         Ollama model (default: from settings)
-v, --verbose       Verbose output

Example:
\$sudo -u www-data /usr/bin/php local/ollamamcp/cli/start_mcp_server.php --host=localhost --port=8080 --model=llama3.2:latest
";
    echo $help;
    exit(0);
}

// Validate parameters
$host = clean_param($options['host'], PARAM_HOST);
$port = clean_param($options['port'], PARAM_INT);
$model = clean_param($options['model'], PARAM_TEXT);

if ($port < 1024 || $port > 65535) {
    cli_error("Port must be between 1024 and 65535");
}

// Display startup info
cli_heading("Ollama MCP Server Starting");
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Model: {$model}\n";
echo "Ollama URL: " . (get_config('local_ollamamcp', 'ollamaserver') ?: 'http://localhost:11434') . "\n";
echo "Process ID: " . getmypid() . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";

if ($options['verbose']) {
    echo "Verbose mode enabled\n";
}

// Load the MCP server class
try {
    require_once($CFG->dirroot . '/local/ollamamcp/classes/mcp/server.php');
    
    if ($options['verbose']) {
        echo "Server class loaded successfully\n";
    }
    
    // Create and start the server
    $server = new \local_ollamamcp\mcp\server($host, $port, $model);
    
    // Set up signal handlers for graceful shutdown
    function signal_handler($signal) {
        global $server;
        echo "\nReceived signal {$signal}, shutting down...\n";
        $server->stop();
        exit(0);
    }
    
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, 'signal_handler');
        pcntl_signal(SIGINT, 'signal_handler');
        if ($options['verbose']) {
            echo "Signal handlers registered\n";
        }
    }
    
    // Start the server
    echo "Starting MCP server...\n";
    $server->start();
    
} catch (Exception $e) {
    cli_error("Failed to start MCP server: " . $e->getMessage());
} catch (Error $e) {
    cli_error("PHP error: " . $e->getMessage());
}
