<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';
require_once __DIR__ . '/renderer_sessions.php';

/*
|--------------------------------------------------------------------------
| Renderer-first runtime model
|--------------------------------------------------------------------------
*/

function gee_get_allowed_streams_for_renderer(array $rendererContext): array
{
    return ['safe', 'hires'];
}

function gee_get_default_stream_for_renderer(array $rendererContext): string
{
    $allowed = gee_get_allowed_streams_for_renderer($rendererContext);

    if (in_array('hires', $allowed, true)) {
        return 'hires';
    }

    return 'safe';
}

function gee_get_renderer_stream_cookie_name(array $rendererContext): string
{
    $rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

    if ($rendererId <= 0) {
        $rendererKey = strtolower((string)($rendererContext['hostname'] ?? 'renderer'));
        $rendererKey = preg_replace('/[^a-z0-9_\-]/', '_', $rendererKey);
        return 'gee_stream_' . $rendererKey;
    }

    return 'gee_stream_renderer_' . $rendererId;
}

function gee_get_selected_stream_for_renderer(array $rendererContext): ?string
{
    $cookieName = gee_get_renderer_stream_cookie_name($rendererContext);

    if (!isset($_COOKIE[$cookieName])) {
        return null;
    }

    $stream = strtolower(trim((string)$_COOKIE[$cookieName]));
    $allowed = gee_get_allowed_streams_for_renderer($rendererContext);

    return in_array($stream, $allowed, true) ? $stream : null;
}

function gee_get_active_stream_for_renderer(array $rendererContext): string
{
    $sessionStream = gee_get_active_stream_from_session_or_default($rendererContext);

    if ($sessionStream !== null) {
        return $sessionStream;
    }

    $selected = gee_get_selected_stream_for_renderer($rendererContext);

    if ($selected !== null) {
        return $selected;
    }

    return gee_get_default_stream_for_renderer($rendererContext);
}

function gee_set_selected_stream_for_renderer(array $rendererContext, string $stream): bool
{
    $stream = strtolower(trim($stream));
    $allowed = gee_get_allowed_streams_for_renderer($rendererContext);

    if (!in_array($stream, $allowed, true)) {
        return false;
    }

    $cookieName = gee_get_renderer_stream_cookie_name($rendererContext);

    return setcookie(
        $cookieName,
        $stream,
        [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]
    );
}

function gee_get_runtime_config_for_renderer_stream(array $rendererContext, string $stream): array
{
    $stream = strtolower(trim($stream));

    switch ($stream) {
        case 'hires':
            return [
                'stream_key' => 'stream_hires',
                'stream_name' => 'Hi-Res',
                'stream_format' => '192000:24:2',
                'playlist_filename' => 'stream_hires.m3u',
                'playlist_name' => 'stream_hires',
                'playlist_directory' => '/var/lib/mpd-hires/playlists',
                'playlist_path' => '/var/lib/mpd-hires/playlists/stream_hires.m3u',
                'mpd_host' => '127.0.0.1',
                'mpd_port' => 6602,
            ];

        case 'safe':
        default:
            return [
                'stream_key' => 'stream_safe',
                'stream_name' => 'Safe',
                'stream_format' => '44100:16:2',
                'playlist_filename' => 'stream_safe.m3u',
                'playlist_name' => 'stream_safe',
                'playlist_directory' => '/var/lib/mpd-safe/playlists',
                'playlist_path' => '/var/lib/mpd-safe/playlists/stream_safe.m3u',
                'mpd_host' => '127.0.0.1',
                'mpd_port' => 6601,
            ];
    }
}

function gee_get_renderer_runtime_context(?array $rendererContext = null): ?array
{
    if (!is_array($rendererContext)) {
        $rendererContext = gee_get_selected_renderer_context();

        if ($rendererContext === null) {
            $rendererContext = gee_get_first_renderer_context();
        }
    }

    if (!is_array($rendererContext)) {
        return null;
    }

    $allowedStreams = gee_get_allowed_streams_for_renderer($rendererContext);
    $activeStream = gee_get_active_stream_for_renderer($rendererContext);
    $streamRuntime = gee_get_runtime_config_for_renderer_stream($rendererContext, $activeStream);

    return array_merge(
        $rendererContext,
        [
            'allowed_streams' => $allowedStreams,
            'active_stream' => $activeStream,
            'default_stream' => gee_get_default_stream_for_renderer($rendererContext),
        ],
        $streamRuntime
    );
}