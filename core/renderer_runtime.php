<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';
require_once __DIR__ . '/renderer_sessions.php';

function gee_get_allowed_streams_for_renderer(array $rendererContext): array
{
    return ['safe', 'hires'];
}

function gee_get_default_stream_for_renderer(array $rendererContext): string
{
    $allowed = gee_get_allowed_streams_for_renderer($rendererContext);
    return in_array('hires', $allowed, true) ? 'hires' : 'safe';
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

function gee_get_renderer_base_config(string $hostname): ?array
{
    return match (strtolower($hostname)) {
        'rose' => [
            'safe_port' => 6601,
            'hires_port' => 6602,
            'safe_key' => 'rose_safe',
            'hires_key' => 'rose_hires',
            'safe_name' => 'Rose Safe',
            'hires_name' => 'Rose Hi-Res',
            'safe_dir' => '/var/lib/mpd-rose-safe/playlists',
            'hires_dir' => '/var/lib/mpd-rose-hires/playlists',
            'safe_file' => 'rose_safe.m3u',
            'hires_file' => 'rose_hires.m3u',
        ],
        'lucy' => [
            'safe_port' => 6603,
            'hires_port' => 6604,
            'safe_key' => 'lucy_safe',
            'hires_key' => 'lucy_hires',
            'safe_name' => 'Lucy Safe',
            'hires_name' => 'Lucy Hi-Res',
            'safe_dir' => '/var/lib/mpd-lucy-safe/playlists',
            'hires_dir' => '/var/lib/mpd-lucy-hires/playlists',
            'safe_file' => 'lucy_safe.m3u',
            'hires_file' => 'lucy_hires.m3u',
        ],
        'veronica' => [
            'safe_port' => 6605,
            'hires_port' => 6606,
            'safe_key' => 'veronica_safe',
            'hires_key' => 'veronica_hires',
            'safe_name' => 'Veronica Safe',
            'hires_name' => 'Veronica Hi-Res',
            'safe_dir' => '/var/lib/mpd-veronica-safe/playlists',
            'hires_dir' => '/var/lib/mpd-veronica-hires/playlists',
            'safe_file' => 'veronica_safe.m3u',
            'hires_file' => 'veronica_hires.m3u',
        ],
        'emily' => [
            'safe_port' => 6607,
            'hires_port' => 6608,
            'safe_key' => 'emily_safe',
            'hires_key' => 'emily_hires',
            'safe_name' => 'Emily Safe',
            'hires_name' => 'Emily Hi-Res',
            'safe_dir' => '/var/lib/mpd-emily-safe/playlists',
            'hires_dir' => '/var/lib/mpd-emily-hires/playlists',
            'safe_file' => 'emily_safe.m3u',
            'hires_file' => 'emily_hires.m3u',
        ],
        default => null,
    };
}

function gee_get_runtime_config_for_renderer_stream(array $rendererContext, string $stream): array
{
    $stream = strtolower(trim($stream));
    $hostname = strtolower(trim((string)($rendererContext['hostname'] ?? '')));
    $base = gee_get_renderer_base_config($hostname);

    if ($base !== null) {
        if ($stream === 'hires') {
            return [
                'stream_key' => $base['hires_key'],
                'stream_name' => $base['hires_name'],
                'stream_format' => '192000:24:2',
                'playlist_filename' => $base['hires_file'],
                'playlist_name' => pathinfo($base['hires_file'], PATHINFO_FILENAME),
                'playlist_directory' => $base['hires_dir'],
                'playlist_path' => $base['hires_dir'] . '/' . $base['hires_file'],
                'mpd_host' => '127.0.0.1',
                'mpd_port' => $base['hires_port'],
            ];
        }

        return [
            'stream_key' => $base['safe_key'],
            'stream_name' => $base['safe_name'],
            'stream_format' => '44100:16:2',
            'playlist_filename' => $base['safe_file'],
            'playlist_name' => pathinfo($base['safe_file'], PATHINFO_FILENAME),
            'playlist_directory' => $base['safe_dir'],
            'playlist_path' => $base['safe_dir'] . '/' . $base['safe_file'],
            'mpd_host' => '127.0.0.1',
            'mpd_port' => $base['safe_port'],
        ];
    }

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