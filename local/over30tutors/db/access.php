<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/over30tutors:appear' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],
];
