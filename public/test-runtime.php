<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderer_runtime.php';

header('Content-Type: application/json');

$context = gee_get_renderer_runtime_context();

echo json_encode($context, JSON_PRETTY_PRINT);
