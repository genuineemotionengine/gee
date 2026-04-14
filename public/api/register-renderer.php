<?php

declare(strict_types=1);

header('Content-Type: application/json');

$BASE_DIR = '/var/lib/gee-core/renderers';
$RUNTIME_BASE_DIR = '/var/lib/gee-core/runtime';
$CORE_HOST = 'geecore.local';
$CORE_SNAPSERVER_PORT = 1704;

$SAFE_SAMPLE_FORMAT = '44100:16:2';
$HIRES_SAMPLE_FORMAT = '192000:24:2';

$PORT_RANGE_START = 6601;
$PORT_RANGE_END = 7999;

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400, array $extra = []): void
{
    respond(array_merge([
        'success' => false,
        'error' => $message,
    ], $extra), $status);
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

function read_json_file(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $json = file_get_contents($path);
    if ($json === false || trim($json) === '') {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function delete_generated_files(string $rendererDir): void
{
    $generatedFiles = [
        $rendererDir . '/profile.json',
        $rendererDir . '/config_version.txt',
        $rendererDir . '/runtime.json',
        $rendererDir . '/mpd.conf',
        $rendererDir . '/mpd.runtime.conf',
        $rendererDir . '/mpd.safe.conf',
        $rendererDir . '/mpd.hires.conf',
        $rendererDir . '/mpd.safe.runtime.conf',
        $rendererDir . '/mpd.hires.runtime.conf',
        $rendererDir . '/snapclient.conf',
    ];

    foreach ($generatedFiles as $file) {
        if (file_exists($file) && !unlink($file)) {
            fail("Failed to remove file during re-registration: {$file}", 500);
        }
    }
}

function build_mpd_config(
    array $profile,
    string $rendererId,
    string $rendererName,
    string $rendererDir,
    string $streamKey,
    string $sampleFormat
): string {
    $musicDir = '/mnt/music';
    $playlistDir = $rendererDir . '/playlists';
    $dbFile = $rendererDir . '/mpd.' . $streamKey . '.db';
    $stateFile = $rendererDir . '/mpd.' . $streamKey . '.state';
    $stickerFile = $rendererDir . '/sticker.' . $streamKey . '.sql';
    $logFile = $rendererDir . '/mpd.' . $streamKey . '.log';
    $fifoPath = '/run/gee/snapfifo-' . $rendererId . '-' . $streamKey;

    return <<<CONF
# ------------------------------------------------------------------
# Gee canonical MPD config
# Renderer ID: {$rendererId}
# Renderer Name: {$rendererName}
# Stream: {$streamKey}
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
    name "Gee {$rendererName} {$streamKey}"
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

function collect_assigned_ports(string $baseDir, string $skipRendererId = ''): array
{
    $assigned = [];

    foreach (glob($baseDir . '/*') ?: [] as $rendererDir) {
        if (!is_dir($rendererDir)) {
            continue;
        }

        $rendererId = basename($rendererDir);
        if ($skipRendererId !== '' && $rendererId === $skipRendererId) {
            continue;
        }

        $runtimePath = $rendererDir . '/runtime.json';
        $runtime = read_json_file($runtimePath);

        if (!is_array($runtime)) {
            continue;
        }

        $streams = $runtime['streams'] ?? null;
        if (!is_array($streams)) {
            continue;
        }

        foreach (['safe', 'hires'] as $streamKey) {
            $stream = $streams[$streamKey] ?? null;
            if (!is_array($stream)) {
                continue;
            }

            $port = $stream['mpd_port'] ?? null;
            if (is_int($port) && $port > 0) {
                $assigned[$port] = true;
            } elseif (is_string($port) && ctype_digit($port)) {
                $assigned[(int)$port] = true;
            }
        }
    }

    return $assigned;
}

function allocate_port_pair(string $baseDir, string $rendererId, int $start, int $end): array
{
    $assigned = collect_assigned_ports($baseDir, $rendererId);

    for ($safePort = $start; $safePort < $end; $safePort += 2) {
        $hiresPort = $safePort + 1;

        if ($hiresPort > $end) {
            break;
        }

        if (!isset($assigned[$safePort]) && !isset($assigned[$hiresPort])) {
            return [$safePort, $hiresPort];
        }
    }

    fail('No available MPD port pair could be allocated.', 500);
}

function build_runtime_definition(
    string $rendererId,
    string $rendererDir,
    int $configVersion,
    int $safePort,
    int $hiresPort,
    string $safeFormat,
    string $hiresFormat
): array {
    $runtimeDir = '/var/lib/gee-core/runtime/' . $rendererId;
    $playlistDir = $runtimeDir . '/playlists';

    return [
        'renderer_id' => $rendererId,
        'config_version' => $configVersion,
        'streams' => [
            'safe' => [
                'mpd_port' => $safePort,
                'format' => $safeFormat,
                'fifo_path' => '/run/gee/snapfifo-' . $rendererId . '-safe',
                'playlist_filename' => $rendererId . '_safe.m3u',
                'playlist_path' => $playlistDir . '/' . $rendererId . '_safe.m3u',
                'mpd_runtime_conf' => $rendererDir . '/mpd.safe.runtime.conf',
                'canonical_mpd_conf' => $rendererDir . '/mpd.safe.conf',
                'runtime_dir' => $runtimeDir . '/safe',
            ],
            'hires' => [
                'mpd_port' => $hiresPort,
                'format' => $hiresFormat,
                'fifo_path' => '/run/gee/snapfifo-' . $rendererId . '-hires',
                'playlist_filename' => $rendererId . '_hires.m3u',
                'playlist_path' => $playlistDir . '/' . $rendererId . '_hires.m3u',
                'mpd_runtime_conf' => $rendererDir . '/mpd.hires.runtime.conf',
                'canonical_mpd_conf' => $rendererDir . '/mpd.hires.conf',
                'runtime_dir' => $runtimeDir . '/hires',
            ],
        ],
    ];
}

$data = get_json_input();

validate_required($data, [
    'renderer_id',
    'renderer_name',
    'hostname',
    'mac_address',
]);

$rendererId = safe_renderer_id((string)$data['renderer_id']);

if ($rendererId === '') {
    fail('Invalid renderer_id');
}

$rendererDir = $BASE_DIR . '/' . $rendererId;
$profilePath = $rendererDir . '/profile.json';
$configVersionPath = $rendererDir . '/config_version.txt';
$runtimePath = $rendererDir . '/runtime.json';
$mpdSafeConfigPath = $rendererDir . '/mpd.safe.conf';
$mpdHiresConfigPath = $rendererDir . '/mpd.hires.conf';
$snapclientConfigPath = $rendererDir . '/snapclient.conf';

if (!is_dir($BASE_DIR)) {
    fail('Renderer base directory is missing on Geecore', 500);
}

ensure_dir($BASE_DIR);
ensure_dir($RUNTIME_BASE_DIR);

$isReregister = is_dir($rendererDir);
$currentVersion = read_version($configVersionPath);
$existingRuntime = read_json_file($runtimePath);

if ($isReregister) {
    delete_generated_files($rendererDir);
} else {
    ensure_dir($rendererDir);
}

write_json_file($profilePath, $data);

$newVersion = $currentVersion + 1;
write_version($configVersionPath, $newVersion);

$rendererName = trim((string)$data['renderer_name']);

$existingSafePort = null;
$existingHiresPort = null;

if (is_array($existingRuntime)) {
    $streams = $existingRuntime['streams'] ?? null;
    if (is_array($streams)) {
        $safe = $streams['safe'] ?? null;
        $hires = $streams['hires'] ?? null;

        if (is_array($safe) && isset($safe['mpd_port']) && ctype_digit((string)$safe['mpd_port'])) {
            $existingSafePort = (int)$safe['mpd_port'];
        }

        if (is_array($hires) && isset($hires['mpd_port']) && ctype_digit((string)$hires['mpd_port'])) {
            $existingHiresPort = (int)$hires['mpd_port'];
        }
    }
}

if (
    is_int($existingSafePort) && $existingSafePort > 0 &&
    is_int($existingHiresPort) && $existingHiresPort === ($existingSafePort + 1)
) {
    $safePort = $existingSafePort;
    $hiresPort = $existingHiresPort;
} else {
    [$safePort, $hiresPort] = allocate_port_pair(
        $BASE_DIR,
        $rendererId,
        $PORT_RANGE_START,
        $PORT_RANGE_END
    );
}

$runtimeDefinition = build_runtime_definition(
    $rendererId,
    $rendererDir,
    $newVersion,
    $safePort,
    $hiresPort,
    $SAFE_SAMPLE_FORMAT,
    $HIRES_SAMPLE_FORMAT
);

write_json_file($runtimePath, $runtimeDefinition);

$mpdSafeConfig = build_mpd_config(
    $data,
    $rendererId,
    $rendererName,
    $rendererDir,
    'safe',
    $SAFE_SAMPLE_FORMAT
);

$mpdHiresConfig = build_mpd_config(
    $data,
    $rendererId,
    $rendererName,
    $rendererDir,
    'hires',
    $HIRES_SAMPLE_FORMAT
);

$snapclientConfig = build_snapclient_config($data, $rendererId, $CORE_HOST, $CORE_SNAPSERVER_PORT);

write_text_file($mpdSafeConfigPath, $mpdSafeConfig);
write_text_file($mpdHiresConfigPath, $mpdHiresConfig);
write_text_file($snapclientConfigPath, $snapclientConfig);

$regenerateScript = '/usr/local/bin/gee-regenerate-audio-runtime.sh';

if (is_file($regenerateScript) && is_executable($regenerateScript)) {
    $output = [];
    $returnCode = 0;

    exec('sudo ' . escapeshellarg($regenerateScript) . ' 2>&1', $output, $returnCode);

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
    'runtime' => $runtimeDefinition,
    'message' => $isReregister
        ? 'Renderer re-registered successfully'
        : 'Renderer registered successfully',
    'generated_files' => [
        'profile.json',
        'config_version.txt',
        'runtime.json',
        'mpd.safe.conf',
        'mpd.hires.conf',
        'snapclient.conf',
    ],
]);