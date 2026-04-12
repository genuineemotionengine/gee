<?php

declare(strict_types=1);

$BASE_DIR = '/var/lib/gee-core/renderers';

function fail(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function safe_renderer_id(string $value): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
}

$rendererId = isset($_GET['renderer_id']) ? safe_renderer_id((string)$_GET['renderer_id']) : '';
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';

if ($rendererId === '') {
    fail('Missing or invalid renderer_id');
}

if ($type !== 'snapclient') {
    fail('Unsupported config type');
}

$rendererDir = $BASE_DIR . '/' . $rendererId;

if (!is_dir($rendererDir)) {
    fail('Renderer not found', 404);
}

$fileMap = [
    'snapclient' => $rendererDir . '/snapclient.conf',
];

$configPath = $fileMap[$type];

if (!is_file($configPath)) {
    fail('Requested config file not found', 404);
}

header('Content-Type: text/plain');
header('Cache-Control: no-store');
readfile($configPath);
exit;