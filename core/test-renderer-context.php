<?php

declare(strict_types=1);

require_once '/var/www/app/core/renderers.php';

$context = gee_get_selected_renderer_context();

if ($context === null) {
    $context = gee_get_first_renderer_context();
}

header('Content-Type: application/json');
echo json_encode($context, JSON_PRETTY_PRINT);