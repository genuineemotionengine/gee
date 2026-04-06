<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/renderer_runtime.php';
require_once __DIR__ . '/../core/renderer_sessions.php';

header('Content-Type: application/json');

$rendererContext = gee_get_selected_renderer_context();

if ($rendererContext === null) {
    $rendererContext = gee_get_first_renderer_context();
}

$session = gee_get_renderer_session_for_context($rendererContext);

echo json_encode([
    'renderer' => $rendererContext,
    'session' => $session,
], JSON_PRETTY_PRINT);