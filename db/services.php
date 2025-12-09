<?php
defined('MOODLE_INTERNAL') || die();

$services = [
    'local_ollamamcp_send_message' => [
        'classname' => 'local_ollamamcp\\external\\send_message',
        'methodname' => 'execute',
        'description' => 'Send message to AI assistant',
        'type' => 'write',
        'capabilities' => '',
        'ajax' => true,
    ],
];
