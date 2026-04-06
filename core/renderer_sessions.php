<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/renderers.php';
require_once __DIR__ . '/renderer_runtime.php';

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
