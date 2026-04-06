<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';

function gee_get_stream_context_from_renderer_globals(): ?array
{
    $rendererContext = $GLOBALS['gee_renderer_context'] ?? null;

    if (!is_array($rendererContext)) {
        $rendererContext = gee_get_selected_renderer_context();

        if ($rendererContext === null) {
            $rendererContext = gee_get_first_renderer_context();
        }
    }

    if (!is_array($rendererContext)) {
        return null;
    }

    $streamKey = $rendererContext['stream_key'] ?? 'stream_safe';

    $streamRuntime = gee_get_stream_runtime_config($streamKey);

    return array_merge($rendererContext, $streamRuntime);
}

function gee_get_stream_runtime_config(string $streamKey): array
{
    switch ($streamKey) {
        case 'stream_hires':
            return [
                'playlist_filename' => 'stream_hires.m3u',
                'playlist_name' => 'stream_hires',
                'playlist_directory' => '/var/lib/mpd-hires/playlists',
                'playlist_path' => '/var/lib/mpd-hires/playlists/stream_hires.m3u',
                'mpd_host' => '127.0.0.1',
                'mpd_port' => 6602,
            ];

        case 'stream_safe':
        default:
            return [
                'playlist_filename' => 'stream_safe.m3u',
                'playlist_name' => 'stream_safe',
                'playlist_directory' => '/var/lib/mpd-safe/playlists',
                'playlist_path' => '/var/lib/mpd-safe/playlists/stream_safe.m3u',
                'mpd_host' => '127.0.0.1',
                'mpd_port' => 6601,
            ];
    }
}

function gee_get_playlist_filename_from_stream(?array $streamContext): string
{
    if (is_array($streamContext) && !empty($streamContext['playlist_filename'])) {
        return $streamContext['playlist_filename'];
    }

    return 'stream_safe.m3u';
}

function gee_get_playlist_name_from_stream(?array $streamContext): string
{
    if (is_array($streamContext) && !empty($streamContext['playlist_name'])) {
        return $streamContext['playlist_name'];
    }

    return 'stream_safe';
}

function gee_get_playlist_directory_from_stream(?array $streamContext): string
{
    if (is_array($streamContext) && !empty($streamContext['playlist_directory'])) {
        return $streamContext['playlist_directory'];
    }

    return '/var/lib/mpd-safe/playlists';
}

function gee_get_playlist_path_from_stream(?array $streamContext): string
{
    if (is_array($streamContext) && !empty($streamContext['playlist_path'])) {
        return $streamContext['playlist_path'];
    }

    return '/var/lib/mpd-safe/playlists/stream_safe.m3u';
}

function gee_get_mpd_host_from_stream(?array $streamContext): string
{
    if (is_array($streamContext) && !empty($streamContext['mpd_host'])) {
        return (string)$streamContext['mpd_host'];
    }

    return '127.0.0.1';
}

function gee_get_mpd_port_from_stream(?array $streamContext): int
{
    if (is_array($streamContext) && !empty($streamContext['mpd_port'])) {
        return (int)$streamContext['mpd_port'];
    }

    return 6601;
}