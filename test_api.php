<?php
// Simple test API
require_once('../../config.php');

header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'data' => [
            'platform_url' => $CFG->wwwroot,
            'site_name' => $CFG->sitename,
            'version' => $CFG->version
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
