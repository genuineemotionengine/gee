<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';

const GEE_RUNTIME_ROOT = '/var/lib/gee-core/runtime';

function gee_get_renderer_dir(string $rendererId): string
{
    return GEE_RENDERERS_DIR . '/' . $rendererId;
}

function gee_get_renderer_runtime_dir(string $rendererId): string
{
    return GEE_RUNTIME_ROOT . '/' . $rendererId;
}

function gee_get_renderer_mpd_conf_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/mpd.conf';
}

function gee_get_renderer_mpd_runtime_conf_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/mpd.runtime.conf';
}

function gee_get_renderer_config_version_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/config_version.txt';
}

function gee_read_renderer_config_version(string $rendererId): string
{
    $path = gee_get_renderer_config_version_path($rendererId);

    if (!is_file($path)) {
        return '';
    }

    $value = file_get_contents($path);
    if ($value === false) {
        return '';
    }

    return trim($value);
}

function gee_parse_mpd_config_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $lines = preg_split('/\R/', $contents);
    if (!is_array($lines)) {
        return [];
    }

    $config = [
        'directives' => [],
        'audio_outputs' => [],
    ];

    $inAudioOutput = false;
    $currentAudioOutput = [];

    foreach ($lines as $rawLine) {
        $line = trim($rawLine);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if ($inAudioOutput) {
            if ($line === '}') {
                if ($currentAudioOutput !== []) {
                    $config['audio_outputs'][] = $currentAudioOutput;
                }

                $currentAudioOutput = [];
                $inAudioOutput = false;
                continue;
            }

            if (preg_match('/^([a-zA-Z0-9_]+)\s+"([^"]*)"$/', $line, $matches)) {
                $currentAudioOutput[$matches[1]] = $matches[2];
            }

            continue;
        }

        if (preg_match('/^audio_output\s*\{$/', $line)) {
            $inAudioOutput = true;
            $currentAudioOutput = [];
            continue;
        }

        if (preg_match('/^([a-zA-Z0-9_]+)\s+"([^"]*)"$/', $line, $matches)) {
            $config['directives'][$matches[1]] = $matches[2];
        }
    }

    return $config;
}

function gee_find_audio_output_by_type(array $parsedConfig, string $type): ?array
{
    $outputs = $parsedConfig['audio_outputs'] ?? null;
    if (!is_array($outputs)) {
        return null;
    }

    foreach ($outputs as $output) {
        if (!is_array($output)) {
            continue;
        }

        if (strcasecmp((string)($output['type'] ?? ''), $type) === 0) {
            return $output;
        }
    }

    return null;
}

function gee_get_mpd_directive(array $parsedConfig, string $key, string $default = ''): string
{
    $directives = $parsedConfig['directives'] ?? null;
    if (!is_array($directives)) {
        return $default;
    }

    $value = $directives[$key] ?? null;
    return is_string($value) ? trim($value) : $default;
}

function gee_build_renderer_playlist_filename(string $rendererId, string $streamKey): string
{
    $rendererId = gee_safe_renderer_id($rendererId);
    $streamKey = gee_safe_renderer_id($streamKey);

    if ($rendererId === '') {
        return '';
    }

    if ($streamKey === '') {
        $streamKey = 'safe';
    }

    return $rendererId . '_' . $streamKey . '.m3u';
}

function gee_is_valid_stream_key(string $streamKey): bool
{
    return in_array($streamKey, ['safe', 'hires'], true);
}

function gee_get_selected_stream(): string
{
    $stream = isset($_COOKIE[GEE_SELECTED_STREAM_COOKIE])
        ? trim((string)$_COOKIE[GEE_SELECTED_STREAM_COOKIE])
        : '';

    return gee_is_valid_stream_key($stream) ? $stream : 'safe';
}

function gee_set_selected_stream_cookie(string $streamKey): bool
{
    if (!gee_is_valid_stream_key($streamKey)) {
        return false;
    }

    return setcookie(
        GEE_SELECTED_STREAM_COOKIE,
        $streamKey,
        [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax',
        ]
    );
}

function gee_read_renderer_runtime_json(string $rendererId): ?array
{
    $path = gee_get_renderer_dir($rendererId) . '/runtime.json';

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

function gee_get_renderer_stream_runtime(array $runtime, ?string $streamKey = null): array
{
    $streamKey = $streamKey !== null && gee_is_valid_stream_key($streamKey)
        ? $streamKey
        : gee_get_selected_stream();

    $rendererId = (string)($runtime['renderer_id'] ?? '');
    $runtimeDir = (string)($runtime['runtime_dir'] ?? '');

    $runtimeJson = $rendererId !== '' ? gee_read_renderer_runtime_json($rendererId) : null;

    $stream = is_array($runtimeJson)
        && isset($runtimeJson['streams'][$streamKey])
        && is_array($runtimeJson['streams'][$streamKey])
            ? $runtimeJson['streams'][$streamKey]
            : [];

    $playlistDirectory = $runtimeDir !== '' ? $runtimeDir . '/playlists' : '';
    $playlistFilename = (string)($stream['playlist_filename'] ?? gee_build_renderer_playlist_filename($rendererId, $streamKey));
    $playlistPath = (string)($stream['playlist_path'] ?? ($playlistDirectory !== '' ? $playlistDirectory . '/' . $playlistFilename : ''));
    $fifoPath = (string)($stream['fifo_path'] ?? '');

    $streamFormat = (string)($stream['format'] ?? '');
    if ($streamKey === 'hires' && preg_match('/^192000:/', $streamFormat)) {
        $streamFormat = '96000:24:2';
    }

    $mpdPort = isset($stream['mpd_port']) ? (int)$stream['mpd_port'] : (int)($runtime['mpd_port'] ?? 6600);
    $runtimeConfPath = (string)($stream['mpd_runtime_conf'] ?? '');
    $runtimeStreamDir = (string)($stream['runtime_dir'] ?? '');

    return array_merge($runtime, [
        'active_stream' => $streamKey,
        'stream_key' => $streamKey,
        'stream_name' => ucfirst($streamKey),
        'stream_format' => $streamFormat,
        'fifo_path' => $fifoPath,
        'playlist_directory' => $playlistDirectory,
        'playlist_filename' => $playlistFilename,
        'playlist_path' => $playlistPath,
        'mpd_port' => $mpdPort,
        'mpd_port_source' => isset($stream['mpd_port']) ? 'runtime_json' : (string)($runtime['mpd_port_source'] ?? 'fallback'),
        'runtime_conf_path' => $runtimeConfPath !== '' ? $runtimeConfPath : (string)($runtime['runtime_conf_path'] ?? ''),
        'stream_runtime_dir' => $runtimeStreamDir,
    ]);
}

function gee_get_renderer_runtime_context(?array $rendererContext = null): ?array
{
    if (!is_array($rendererContext)) {
        $rendererContext = gee_get_selected_or_first_renderer_context();
    }

    if (!is_array($rendererContext)) {
        return null;
    }

    $rendererId = gee_safe_renderer_id((string)($rendererContext['renderer_id'] ?? ''));
    if ($rendererId === '') {
        return null;
    }

    $rendererDir = gee_get_renderer_dir($rendererId);
    $runtimeDir = gee_get_renderer_runtime_dir($rendererId);

    $runtimeConfPath = gee_get_renderer_mpd_runtime_conf_path($rendererId);
    $canonicalConfPath = gee_get_renderer_mpd_conf_path($rendererId);

    $runtimeParsed = gee_parse_mpd_config_file($runtimeConfPath);
    $canonicalParsed = gee_parse_mpd_config_file($canonicalConfPath);

    $runtimeFifoOutput = gee_find_audio_output_by_type($runtimeParsed, 'fifo');
    $canonicalFifoOutput = gee_find_audio_output_by_type($canonicalParsed, 'fifo');

    $musicDirectory = gee_get_mpd_directive($runtimeParsed, 'music_directory');
    if ($musicDirectory === '') {
        $musicDirectory = gee_get_mpd_directive($canonicalParsed, 'music_directory', GEE_MUSIC_ROOT);
    }

    $playlistDirectory = gee_get_mpd_directive($runtimeParsed, 'playlist_directory');
    if ($playlistDirectory === '') {
        $playlistDirectory = gee_get_mpd_directive(
            $canonicalParsed,
            'playlist_directory',
            $runtimeDir . '/playlists'
        );
    }

    $dbFile = gee_get_mpd_directive($runtimeParsed, 'db_file');
    if ($dbFile === '') {
        $dbFile = gee_get_mpd_directive($canonicalParsed, 'db_file', $runtimeDir . '/mpd.db');
    }

    $logFile = gee_get_mpd_directive($runtimeParsed, 'log_file');
    if ($logFile === '') {
        $logFile = gee_get_mpd_directive($canonicalParsed, 'log_file', $runtimeDir . '/mpd.log');
    }

    $stateFile = gee_get_mpd_directive($runtimeParsed, 'state_file');
    if ($stateFile === '') {
        $stateFile = gee_get_mpd_directive($canonicalParsed, 'state_file', $runtimeDir . '/mpd.state');
    }

    $stickerFile = gee_get_mpd_directive($runtimeParsed, 'sticker_file');
    if ($stickerFile === '') {
        $stickerFile = gee_get_mpd_directive($canonicalParsed, 'sticker_file', $runtimeDir . '/sticker.sql');
    }

    $bindToAddress = gee_get_mpd_directive($runtimeParsed, 'bind_to_address');
    if ($bindToAddress === '') {
        $bindToAddress = gee_get_mpd_directive($canonicalParsed, 'bind_to_address', '127.0.0.1');
    }

    $fifoPath = '';
    $streamFormat = '';

    if (is_array($runtimeFifoOutput)) {
        $fifoPath = trim((string)($runtimeFifoOutput['path'] ?? ''));
        $streamFormat = trim((string)($runtimeFifoOutput['format'] ?? ''));
    }

    if ($fifoPath === '' && is_array($canonicalFifoOutput)) {
        $fifoPath = trim((string)($canonicalFifoOutput['path'] ?? ''));
    }

    if ($streamFormat === '' && is_array($canonicalFifoOutput)) {
        $streamFormat = trim((string)($canonicalFifoOutput['format'] ?? ''));
    }

    if ($fifoPath === '') {
        $fifoPath = '/run/gee/snapfifo-' . $rendererId;
    }

    if ($streamFormat === '') {
        $streamFormat = '44100:16:2';
    }

    $portSource = 'fallback';
    $portRaw = gee_get_mpd_directive($runtimeParsed, 'port');

    if ($portRaw !== '') {
        $portSource = 'runtime_conf';
    } else {
        $portRaw = gee_get_mpd_directive($canonicalParsed, 'port');
        if ($portRaw !== '') {
            $portSource = 'canonical_conf';
        }
    }

    $mpdPort = 6600;
    if ($portRaw !== '' && ctype_digit($portRaw)) {
        $parsedPort = (int)$portRaw;
        if ($parsedPort > 0 && $parsedPort <= 65535) {
            $mpdPort = $parsedPort;
        }
    }

    $runtimeReady = is_dir($runtimeDir) && is_dir($playlistDirectory);

    $baseRuntime = array_merge($rendererContext, [
        'renderer_dir' => $rendererDir,
        'runtime_dir' => $runtimeDir,
        'config_version' => gee_read_renderer_config_version($rendererId),

        'runtime_ready' => $runtimeReady,
        'runtime_conf_path' => $runtimeConfPath,
        'canonical_conf_path' => $canonicalConfPath,

        'music_directory' => $musicDirectory,
        'playlist_directory' => $playlistDirectory,

        'db_file' => $dbFile,
        'log_file' => $logFile,
        'state_file' => $stateFile,
        'sticker_file' => $stickerFile,

        'stream_format' => $streamFormat,
        'fifo_path' => $fifoPath,

        'bind_to_address' => $bindToAddress,
        'mpd_host' => $bindToAddress !== '' ? $bindToAddress : '127.0.0.1',
        'mpd_port' => $mpdPort,
        'mpd_port_source' => $portSource,
    ]);

    return gee_get_renderer_stream_runtime($baseRuntime);
}

function gee_get_active_runtime(): ?array
{
    $rendererContext = gee_get_selected_or_first_renderer_context();

    if (!is_array($rendererContext)) {
        return null;
    }

    $runtime = gee_get_renderer_runtime_context($rendererContext);

    if (!is_array($runtime)) {
        return null;
    }

    return gee_get_renderer_stream_runtime($runtime);
}