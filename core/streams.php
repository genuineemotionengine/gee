<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';
require_once __DIR__ . '/config.php';

function gee_get_stream_runtime_config(string $streamKey): array
{
    switch ($streamKey) {
        case GEE_STREAM_HIRES_KEY:
            return [
                'playlist_filename' => GEE_STREAM_HIRES_PLAYLIST_FILE,
                'playlist_name' => pathinfo(GEE_STREAM_HIRES_PLAYLIST_FILE, PATHINFO_FILENAME),
                'playlist_directory' => GEE_STREAM_HIRES_PLAYLIST_DIR,
                'playlist_path' => rtrim(GEE_STREAM_HIRES_PLAYLIST_DIR, '/') . '/' . GEE_STREAM_HIRES_PLAYLIST_FILE,
                'mpd_host' => GEE_STREAM_HIRES_MPD_HOST,
                'mpd_port' => GEE_STREAM_HIRES_MPD_PORT,
            ];

        case GEE_STREAM_SAFE_KEY:
        default:
            return [
                'playlist_filename' => GEE_STREAM_SAFE_PLAYLIST_FILE,
                'playlist_name' => pathinfo(GEE_STREAM_SAFE_PLAYLIST_FILE, PATHINFO_FILENAME),
                'playlist_directory' => GEE_STREAM_SAFE_PLAYLIST_DIR,
                'playlist_path' => rtrim(GEE_STREAM_SAFE_PLAYLIST_DIR, '/') . '/' . GEE_STREAM_SAFE_PLAYLIST_FILE,
                'mpd_host' => GEE_STREAM_SAFE_MPD_HOST,
                'mpd_port' => GEE_STREAM_SAFE_MPD_PORT,
            ];
    }
}

function gee_apply_stream_runtime(?array $rendererContext): array
{
    $baseContext = is_array($rendererContext) ? $rendererContext : [];
    $streamKey = $baseContext['stream_key'] ?? GEE_STREAM_SAFE_KEY;

    return array_merge($baseContext, gee_get_stream_runtime_config((string) $streamKey));
}

function gee_get_stream_context_from_renderer_globals(): ?array
{
    $rendererContext = $GLOBALS['gee_renderer_context'] ?? null;

    if (!is_array($rendererContext)) {
        $rendererContext = gee_resolve_renderer_context();
    }

    if (!is_array($rendererContext)) {
        return null;
    }

    return gee_apply_stream_runtime($rendererContext);
}

function gee_resolve_stream_context(string $cookieName = 'gee_selected_renderer'): ?array
{
    $rendererContext = gee_resolve_renderer_context($cookieName);

    if (!is_array($rendererContext)) {
        return null;
    }

    $streamContext = gee_apply_stream_runtime($rendererContext);
    $GLOBALS['gee_renderer_context'] = $streamContext;

    return $streamContext;
}

function gee_get_playlist_filename_from_stream(?array $streamContext): string
{
    return (is_array($streamContext) && !empty($streamContext['playlist_filename']))
        ? (string) $streamContext['playlist_filename']
        : GEE_STREAM_SAFE_PLAYLIST_FILE;
}

function gee_get_playlist_name_from_stream(?array $streamContext): string
{
    return (is_array($streamContext) && !empty($streamContext['playlist_name']))
        ? (string) $streamContext['playlist_name']
        : pathinfo(GEE_STREAM_SAFE_PLAYLIST_FILE, PATHINFO_FILENAME);
}

function gee_get_playlist_directory_from_stream(?array $streamContext): string
{
    return (is_array($streamContext) && !empty($streamContext['playlist_directory']))
        ? (string) $streamContext['playlist_directory']
        : GEE_STREAM_SAFE_PLAYLIST_DIR;
}

function gee_get_playlist_path_from_stream(?array $streamContext): string
{
    return (is_array($streamContext) && !empty($streamContext['playlist_path']))
        ? (string) $streamContext['playlist_path']
        : rtrim(GEE_STREAM_SAFE_PLAYLIST_DIR, '/') . '/' . GEE_STREAM_SAFE_PLAYLIST_FILE;
}

function gee_get_mpd_host_from_stream(?array $streamContext): string
{
    return (is_array($streamContext) && !empty($streamContext['mpd_host']))
        ? (string) $streamContext['mpd_host']
        : GEE_STREAM_SAFE_MPD_HOST;
}

function gee_get_mpd_port_from_stream(?array $streamContext): int
{
    return (is_array($streamContext) && !empty($streamContext['mpd_port']))
        ? (int) $streamContext['mpd_port']
        : GEE_STREAM_SAFE_MPD_PORT;
}
