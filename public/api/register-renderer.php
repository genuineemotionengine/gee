<?php

declare(strict_types=1);

header('Content-Type: application/json');

$BASE_DIR = '/var/lib/gee-core/renderers';

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400): void
{
    respond([
        'success' => false,
        'error' => $message
    ], $status);
}

function get_json_input(): array
{
    $input = file_get_contents('php://input');

    if ($input === false || trim($input) === '') {
        fail('Empty request body');
    }

    $data = json_decode($input, true);

    if (!is_array($data)) {
        fail('Invalid JSON');
    }

    return $data;
}

function validate_required(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            fail("Missing required field: {$field}");
        }
    }
}

function safe_renderer_id(string $value): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
}

function ensure_dir(string $path): void
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            fail("Failed to create directory: {$path}", 500);
        }
    }
}

function write_json_file(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        fail("Failed to encode JSON for {$path}", 500);
    }

    if (file_put_contents($path, $json . PHP_EOL) === false) {
        fail("Failed to write file: {$path}", 500);
    }
}

function read_version(string $path): int
{
    if (!file_exists($path)) {
        return 0;
    }

    $value = trim((string)file_get_contents($path));
    return ctype_digit($value) ? (int)$value : 0;
}

function write_version(string $path, int $version): void
{
    if (file_put_contents($path, (string)$version . PHP_EOL) === false) {
        fail("Failed to write config version: {$path}", 500);
    }
}

function delete_generated_files(string $rendererDir): void
{
    $generatedFiles = [
        $rendererDir . '/profile.json',
        $rendererDir . '/config_version.txt',
        $rendererDir . '/mpd.conf',
        $rendererDir . '/snapclient.conf',
    ];

    foreach ($generatedFiles as $file) {
        if (file_exists($file) && !unlink($file)) {
            fail("Failed to remove file during re-registration: {$file}", 500);
        }
    }
}

$data = get_json_input();

validate_required($data, [
    'renderer_id',
    'renderer_name',
    'hostname',
    'mac_address'
]);

$rendererId = safe_renderer_id((string)$data['renderer_id']);

if ($rendererId === '') {
    fail('Invalid renderer_id');
}

$rendererDir = $BASE_DIR . '/' . $rendererId;
$profilePath = $rendererDir . '/profile.json';
$configVersionPath = $rendererDir . '/config_version.txt';

if (!is_dir($BASE_DIR)) {
    fail('Renderer base directory is missing on Geecore', 500);
}

$isReregister = is_dir($rendererDir);
$currentVersion = read_version($configVersionPath);

if ($isReregister) {
    delete_generated_files($rendererDir);
} else {
    ensure_dir($rendererDir);
}

write_json_file($profilePath, $data);

$newVersion = $currentVersion + 1;
write_version($configVersionPath, $newVersion);

respond([
    'success' => true,
    'renderer_id' => $rendererId,
    'config_version' => $newVersion,
    'message' => $isReregister
        ? 'Renderer re-registered successfully'
        : 'Renderer registered successfully'
]);