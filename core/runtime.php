<?php

declare(strict_types=1);

require_once __DIR__ . '/renderers.php';

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

    return array_merge($rendererContext, [
        'active_stream' => 'safe',
        'stream_key' => 'safe',
        'stream_name' => 'Safe',
        'stream_format' => '44100:16:2',
        'mpd_host' => '127.0.0.1',
        'mpd_port' => 6600,
        'fifo_path' => '/run/gee/snapfifo-' . $rendererId,
        'playlist_directory' => '/var/lib/gee-core/runtime/' . $rendererId . '/playlists',
        'playlist_filename' => $rendererId . '_safe.m3u',
        'playlist_path' => '/var/lib/gee-core/runtime/' . $rendererId . '/playlists/' . $rendererId . '_safe.m3u',
    ]);
}