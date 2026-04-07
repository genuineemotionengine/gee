<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/renderers.php';
require_once __DIR__ . '/renderer_runtime.php';
require_once __DIR__ . '/../api/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

function gee_get_renderer_session(int $rendererId): ?array
{
    $conn = gee_db();

    $stmt = $conn->prepare("
        SELECT
            renderer_id,
            active_stream,
            current_track_uri,
            current_track_pos,
            elapsed_seconds,
            playback_state,
            updated_at
        FROM renderer_sessions
        WHERE renderer_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare renderer session query: ' . $conn->error);
    }

    $stmt->bind_param('i', $rendererId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Failed to execute renderer session query: ' . $error);
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return $row ?: null;
}

function gee_ensure_renderer_session(int $rendererId, string $defaultStream = 'hires'): array
{
    $existing = gee_get_renderer_session($rendererId);

    if ($existing !== null) {
        return $existing;
    }

    $conn = gee_db();

    $stmt = $conn->prepare("
        INSERT INTO renderer_sessions (
            renderer_id,
            active_stream,
            current_track_uri,
            current_track_pos,
            elapsed_seconds,
            playback_state
        ) VALUES (?, ?, NULL, 0, 0, 'stop')
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare renderer session insert: ' . $conn->error);
    }

    $stmt->bind_param('is', $rendererId, $defaultStream);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Failed to create renderer session: ' . $error);
    }

    $stmt->close();

    $created = gee_get_renderer_session($rendererId);

    if ($created === null) {
        throw new RuntimeException('Renderer session was created but could not be reloaded.');
    }

    return $created;
}

function gee_save_renderer_session(
    int $rendererId,
    string $activeStream,
    ?string $currentTrackUri,
    int $currentTrackPos,
    float $elapsedSeconds,
    string $playbackState
): bool {
    $conn = gee_db();

    $stmt = $conn->prepare("
        INSERT INTO renderer_sessions (
            renderer_id,
            active_stream,
            current_track_uri,
            current_track_pos,
            elapsed_seconds,
            playback_state
        ) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            active_stream = VALUES(active_stream),
            current_track_uri = VALUES(current_track_uri),
            current_track_pos = VALUES(current_track_pos),
            elapsed_seconds = VALUES(elapsed_seconds),
            playback_state = VALUES(playback_state)
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare renderer session upsert: ' . $conn->error);
    }

    $stmt->bind_param(
        'issids',
        $rendererId,
        $activeStream,
        $currentTrackUri,
        $currentTrackPos,
        $elapsedSeconds,
        $playbackState
    );

    $ok = $stmt->execute();
    $error = $stmt->error;

    $stmt->close();

    if (!$ok) {
        throw new RuntimeException('Failed to save renderer session: ' . $error);
    }

    return true;
}

function gee_get_renderer_session_for_context(?array $rendererContext = null): ?array
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

    $rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

    if ($rendererId <= 0) {
        return null;
    }

    $defaultStream = gee_get_default_stream_for_renderer($rendererContext);

    return gee_ensure_renderer_session($rendererId, $defaultStream);
}

function gee_get_active_stream_from_session_or_default(?array $rendererContext = null): ?string
{
    $session = gee_get_renderer_session_for_context($rendererContext);

    if ($session !== null && !empty($session['active_stream'])) {
        return (string)$session['active_stream'];
    }

    if (is_array($rendererContext)) {
        return gee_get_default_stream_for_renderer($rendererContext);
    }

    return null;
}

function gee_capture_renderer_session_from_mpd(?array $rendererContext = null): ?array
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

    $rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

    if ($rendererId <= 0) {
        return null;
    }

    $runtime = gee_get_renderer_runtime_context($rendererContext);

    if (!is_array($runtime)) {
        return null;
    }

    $activeStream = (string)($runtime['active_stream'] ?? gee_get_default_stream_for_renderer($rendererContext));
    $mpdHost = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $mpdPort = (int)($runtime['mpd_port'] ?? 6601);

    try {
        $mphpd = new MphpD([
            'host' => $mpdHost,
            'port' => $mpdPort,
            'timeout' => 5,
        ]);

        $mphpd->connect();

        $status = $mphpd->status();
        $currentSong = $mphpd->player()->current_song();

        $currentTrackUri = null;
        $currentTrackPos = 0;
        $elapsedSeconds = 0.0;
        $playbackState = (string)($status['state'] ?? 'stop');

        if (is_array($currentSong)) {
            $currentTrackUri = !empty($currentSong['file']) ? (string)$currentSong['file'] : null;
            $currentTrackPos = isset($currentSong['pos']) ? (int)$currentSong['pos'] : 0;
        }

        if (isset($status['elapsed'])) {
            $elapsedSeconds = (float)$status['elapsed'];
        }

        gee_save_renderer_session(
            $rendererId,
            $activeStream,
            $currentTrackUri,
            $currentTrackPos,
            $elapsedSeconds,
            $playbackState
        );

        return gee_get_renderer_session($rendererId);

    } catch (MPDException $e) {
        throw new RuntimeException('Failed to capture renderer session from MPD: ' . $e->getMessage(), 0, $e);
    }
}

function gee_set_renderer_active_stream(int $rendererId, string $activeStream): bool
{
    $session = gee_ensure_renderer_session($rendererId, $activeStream);

    return gee_save_renderer_session(
        $rendererId,
        $activeStream,
        $session['current_track_uri'] ?? null,
        (int)($session['current_track_pos'] ?? 0),
        (float)($session['elapsed_seconds'] ?? 0),
        (string)($session['playback_state'] ?? 'stop')
    );
}

function gee_set_renderer_active_stream_for_context(?array $rendererContext, string $activeStream): bool
{
    if (!is_array($rendererContext)) {
        $rendererContext = gee_get_selected_renderer_context();

        if ($rendererContext === null) {
            $rendererContext = gee_get_first_renderer_context();
        }
    }

    if (!is_array($rendererContext)) {
        return false;
    }

    $rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

    if ($rendererId <= 0) {
        return false;
    }

    return gee_set_renderer_active_stream($rendererId, $activeStream);
}

function gee_restore_renderer_session_to_mpd(?array $rendererContext = null): array
{
    if (!is_array($rendererContext)) {
        $rendererContext = gee_get_selected_renderer_context();

        if ($rendererContext === null) {
            $rendererContext = gee_get_first_renderer_context();
        }
    }

    if (!is_array($rendererContext)) {
        throw new RuntimeException('No renderer context available for restore.');
    }

    $rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

    if ($rendererId <= 0) {
        throw new RuntimeException('Invalid renderer id for restore.');
    }

    $session = gee_get_renderer_session_for_context($rendererContext);

    if (!is_array($session)) {
        throw new RuntimeException('No renderer session available for restore.');
    }

    $runtime = gee_get_renderer_runtime_context($rendererContext);

    if (!is_array($runtime)) {
        throw new RuntimeException('No renderer runtime context available for restore.');
    }

    $mpdHost = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $mpdPort = (int)($runtime['mpd_port'] ?? 6601);

    $currentTrackUri = $session['current_track_uri'] ?? null;
    $currentTrackPos = (int)($session['current_track_pos'] ?? 0);
    $elapsedSeconds = (float)($session['elapsed_seconds'] ?? 0);
    $playbackState = (string)($session['playback_state'] ?? 'stop');

    $mphpd = new MphpD([
        'host' => $mpdHost,
        'port' => $mpdPort,
        'timeout' => 5,
    ]);

    try {
        $mphpd->connect();

        if ($currentTrackUri !== null && $currentTrackUri !== '') {
            $queue = $mphpd->queue()->get();
            $foundPos = null;

            if (is_array($queue)) {
                foreach ($queue as $idx => $track) {
                    if (!empty($track['file']) && (string)$track['file'] === (string)$currentTrackUri) {
                        if (isset($track['pos'])) {
                            $foundPos = (int)$track['pos'];
                        } else {
                            $foundPos = (int)$idx;
                        }
                        break;
                    }
                }
            }

            // If the track is not already in the queue, rebuild a minimal queue with just that track
            if ($foundPos === null) {
                $mphpd->queue()->clear();
                $mphpd->queue()->add($currentTrackUri);
                $foundPos = 0;
            }

            $mphpd->player()->play($foundPos);

            if ($elapsedSeconds > 0) {
                // Use seekcur if available in your MPD/MphpD combo
                try {
                    $mphpd->player()->seekcur((string)$elapsedSeconds);
                } catch (\Throwable $e) {
                    // Fallback: try play position only if seek is not supported cleanly
                }
            }

            if ($playbackState === 'pause') {
                $mphpd->player()->pause();
            } elseif ($playbackState === 'stop') {
                // safest stop behavior after loading track
                try {
                    $mphpd->sendCommand('stop');
                } catch (\Throwable $e) {
                    // ignore if not supported
                }
            }
        }

        return [
            'status' => 'ok',
            'renderer_id' => $rendererId,
            'active_stream' => $session['active_stream'] ?? null,
            'current_track_uri' => $currentTrackUri,
            'current_track_pos' => $currentTrackPos,
            'elapsed_seconds' => $elapsedSeconds,
            'playback_state' => $playbackState,
            'mpd_host' => $mpdHost,
            'mpd_port' => $mpdPort,
        ];

    } catch (MPDException $e) {
        throw new RuntimeException('Failed to restore renderer session to MPD: ' . $e->getMessage(), 0, $e);
    }
}

function gee_restore_selected_renderer_session_to_mpd(): array
{
    $rendererContext = gee_get_selected_renderer_context();

    if ($rendererContext === null) {
        $rendererContext = gee_get_first_renderer_context();
    }

    return gee_restore_renderer_session_to_mpd($rendererContext);
}