<?php

declare(strict_types=1);

header('Content-Type: application/json');

$BASE_DIR = '/var/lib/gee-core/renderers';
$CORE_HOST = 'geecore.local';
$CORE_SNAPSERVER_PORT = 1704;
$DEFAULT_SAMPLE_FORMAT = '44100:16:2';

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

function write_text_file(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
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

function build_mpd_config(array $profile, string $rendererId, string $rendererDir, string $sampleFormat): string
{
    $rendererName = (string)$profile['renderer_name'];
    $musicDir = '/mnt/music';
    $playlistDir = $rendererDir . '/playlists';
    $dbFile = $rendererDir . '/mpd.db';
    $stateFile = $rendererDir . '/mpd.state';
    $stickerFile = $rendererDir . '/sticker.sql';
    $logFile = $rendererDir . '/mpd.log';
    $fifoPath = '/run/gee/snapfifo-' . $rendererId;

    return <<<CONF
# ------------------------------------------------------------------
# Gee canonical MPD config
# Renderer ID: {$rendererId}
# Renderer Name: {$rendererName}
#
# This is the Geecore-generated canonical config artifact.
# Runtime service-specific values can be layered on later.
# ------------------------------------------------------------------

music_directory "{$musicDir}"
playlist_directory "{$playlistDir}"
db_file "{$dbFile}"
log_file "{$logFile}"
state_file "{$stateFile}"
sticker_file "{$stickerFile}"

auto_update "yes"
follow_inside_symlinks "yes"
follow_outside_symlinks "yes"

audio_output {
    type "fifo"
    name "Gee {$rendererName}"
    path "{$fifoPath}"
    format "{$sampleFormat}"
}

CONF;
}

function build_snapclient_config(array $profile, string $rendererId, string $coreHost, int $corePort): string
{
    $rendererName = (string)$profile['renderer_name'];
    $hostname = (string)$profile['hostname'];
    $macAddress = (string)$profile['mac_address'];
    $ipAddress = isset($profile['ip_address']) ? (string)$profile['ip_address'] : '';
    $model = isset($profile['model']) ? (string)$profile['model'] : '';
    $installerVersion = isset($profile['installer_version']) ? (string)$profile['installer_version'] : '';

    return <<<CONF
# ------------------------------------------------------------------
# Gee canonical Snapclient config
# Renderer ID: {$rendererId}
# Renderer Name: {$rendererName}
#
# This is the Geecore-generated canonical renderer config artifact.
# It is not yet being pushed into the live Snapclient service layer.
# ------------------------------------------------------------------

[gee]
renderer_id = {$rendererId}
renderer_name = {$rendererName}
hostname = {$hostname}
mac_address = {$macAddress}
ip_address = {$ipAddress}
model = {$model}
installer_version = {$installerVersion}

[server]
host = {$coreHost}
port = {$corePort}

CONF;
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
$mpdConfigPath = $rendererDir . '/mpd.conf';
$snapclientConfigPath = $rendererDir . '/snapclient.conf';

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

$mpdConfig = build_mpd_config($data, $rendererId, $rendererDir, $DEFAULT_SAMPLE_FORMAT);
$snapclientConfig = build_snapclient_config($data, $rendererId, $CORE_HOST, $CORE_SNAPSERVER_PORT);

write_text_file($mpdConfigPath, $mpdConfig);
write_text_file($snapclientConfigPath, $snapclientConfig);

$regenerateScript = '/usr/local/bin/gee-regenerate-audio-runtime.sh';

if (is_file($regenerateScript) && is_executable($regenerateScript)) {
    $output = [];
    $returnCode = 0;

    exec($regenerateScript . ' 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        fail(
            'Renderer registered, but audio runtime regeneration failed: ' . implode(' | ', $output),
            500
        );
    }
}

respond([
    'success' => true,
    'renderer_id' => $rendererId,
    'config_version' => $newVersion,
    'message' => $isReregister
        ? 'Renderer re-registered successfully'
        : 'Renderer registered successfully',
    'generated_files' => [
        'profile.json',
        'config_version.txt',
        'mpd.conf',
        'snapclient.conf'
    ]
]);