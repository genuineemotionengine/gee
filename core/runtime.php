<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';

const GEE_RUNTIME_ROOT = '/var/lib/gee-core/runtime';

/**
 * Return the absolute path to a renderer's canonical config directory.
 */
function gee_get_renderer_dir(string $rendererId): string
{
    return GEE_RENDERERS_DIR . '/' . $rendererId;
}

/**
 * Return the absolute path to a renderer's live runtime directory.
 */
function gee_get_renderer_runtime_dir(string $rendererId): string
{
    return GEE_RUNTIME_ROOT . '/' . $rendererId;
}

/**
 * Return the path to the renderer's canonical MPD config.
 */
function gee_get_renderer_mpd_conf_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/mpd.conf';
}

/**
 * Return the path to the renderer's live MPD runtime config.
 */
function gee_get_renderer_mpd_runtime_conf_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/mpd.runtime.conf';
}

/**
 * Return the path to the renderer's config version file.
 */
function gee_get_renderer_config_version_path(string $rendererId): string
{
    return gee_get_renderer_dir($rendererId) . '/config_version.txt';
}

/**
 * Read a renderer config version if present.
 */
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

/**
 * Parse a simple MPD config file into a structured array.
 *
 * Supports top-level directives like:
 *   music_directory "/mnt/music"
 *   bind_to_address "127.0.0.1"
 *   port "6600"
 *
 * And audio_output blocks like:
 *   audio_output {
 *       type "fifo"
 *       path "/run/gee/snapfifo-rose"
 *       format "44100:16:2"
 *   }
 */
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

/**
 * Return the first audio_output block matching the requested type.
 */
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

/**
 * Return a directive value from a parsed config file.
 */
function gee_get_mpd_directive(array $parsedConfig, string $key, string $default = ''): string
{
    $directives = $parsedConfig['directives'] ?? null;
    if (!is_array($directives)) {
        return $default;
    }

    $value = $directives[$key] ?? null;
    return is_string($value) ? trim($value) : $default;
}

/**
 * Build the standard playlist filename for the renderer's active stream.
 */
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

/**
 * Return a runtime-aware renderer context.
 */
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

    // Current live platform behaviour is safe stream only.
    $activeStream = 'safe';
    $streamKey = 'safe';
    $streamName = 'Safe';

    $playlistFilename = gee_build_renderer_playlist_filename($rendererId, $streamKey);
    $playlistPath = rtrim($playlistDirectory, '/') . '/' . $playlistFilename;

    $runtimeReady = is_dir($runtimeDir)
        && is_dir($playlistDirectory)
        && is_file($runtimeConfPath);

    return array_merge($rendererContext, [
        'renderer_dir' => $rendererDir,
        'runtime_dir' => $runtimeDir,
        'config_version' => gee_read_renderer_config_version($rendererId),

        'runtime_ready' => $runtimeReady,
        'runtime_conf_path' => $runtimeConfPath,
        'canonical_conf_path' => $canonicalConfPath,

        'music_directory' => $musicDirectory,
        'playlist_directory' => $playlistDirectory,
        'playlist_filename' => $playlistFilename,
        'playlist_path' => $playlistPath,

        'db_file' => $dbFile,
        'log_file' => $logFile,
        'state_file' => $stateFile,
        'sticker_file' => $stickerFile,

        'active_stream' => $activeStream,
        'stream_key' => $streamKey,
        'stream_name' => $streamName,
        'stream_format' => $streamFormat,
        'fifo_path' => $fifoPath,

        'bind_to_address' => $bindToAddress,
        'mpd_host' => $bindToAddress !== '' ? $bindToAddress : '127.0.0.1',
        'mpd_port' => $mpdPort,
        'mpd_port_source' => $portSource,
    ]);
}