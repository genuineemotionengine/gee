<?php


declare(strict_types=1);

require_once __DIR__ . '/renderers.php';

/*
|--------------------------------------------------------------------------
| Renderer-first runtime model
|--------------------------------------------------------------------------
|
| Renderer is the parent context.
| Stream exists only within the renderer.
|
| Rule set implemented here:
| - renderer must always come first
| - only renderer-owned streams are allowed
| - highest supported stream is selected by default
| - user may override stream within renderer context
|
*/

/**
 * Return the supported streams for a renderer.
 *
 * For now, all current renderers support:
 * - safe
 * - hires
 *
 * This keeps the model aligned with your current rule set:
 *   Rose      -> safe, hires
 *   Lucy      -> safe, hires
 *   Veronica  -> safe, hires
 *   Emily     -> safe, hires
 *   Olivia    -> safe, hires (when added)
 *
 * Later we can make this capability-driven from hardware fields.
 */
function gee_get_allowed_streams_for_renderer(array $rendererContext): array
{
    return ['safe', 'hires'];
}

/**
 * Return the best default stream for a renderer.
 *
 * Rule:
 * - highest supported stream first
 * - fallback to safe
 */
function gee_get_default_stream_for_renderer(array $rendererContext): string
{
    $allowed = gee_get_allowed_streams_for_renderer($rendererContext);

    if (in_array('hires', $allowed, true)) {
        return 'hires';
    }

    return 'safe';
}

/**
 * Build a per-renderer cookie name for remembering stream choice.
 */
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

/**
 * Read the selected stream for this renderer from cookie, if valid.
 */
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

/**
 * Return the active stream for the renderer.
 *
 * Order:
 * 1. stored per-renderer stream preference
 * 2. best default stream for renderer
 */
function gee_get_active_stream_for_renderer(array $rendererContext): string
{
    $selected = gee_get_selected_stream_for_renderer($rendererContext);

    if ($selected !== null) {
        return $selected;
    }

    return gee_get_default_stream_for_renderer($rendererContext);
}

/**
 * Persist the selected stream for a renderer.
 */
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

/**
 * Return runtime config for a renderer-owned stream.
 *
 * IMPORTANT:
 * For now we keep your existing working MPD/playlist model:
 * - safe  -> 6601 / /var/lib/mpd-safe/playlists
 * - hires -> 6602 / /var/lib/mpd-hires/playlists
 *
 * This is a transitional step.
 *
 * Later, for full Rule 7 and renderer-session preservation,
 * we will move toward renderer-owned session state with
 * stream handover preserving queue/position.
 */
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

/**
 * Resolve full runtime context for the selected renderer.
 */
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
