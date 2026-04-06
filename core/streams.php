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

    return is_array($rendererContext) ? $rendererContext : null;
}

function gee_get_playlist_filename_from_stream(?array $streamContext): string
{
    if (
        is_array($streamContext) &&
        !empty($streamContext['stream_key'])
    ) {
        return $streamContext['stream_key'] . '.m3u';
    }

    return 'app.m3u';
}

function gee_get_playlist_path_from_stream(?array $streamContext): string
{
    return '/var/lib/mpd/playlists/' . gee_get_playlist_filename_from_stream($streamContext);
}