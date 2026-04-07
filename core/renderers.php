<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function gee_get_renderer_context(int $rendererId): ?array
{
    $conn = gee_db();

    $stmt = $conn->prepare("
        SELECT
            r.id AS renderer_id,
            r.hostname,
            r.ip,
            r.platform,
            r.audio_profile,
            r.alsa_device,
            r.max_sample_rate,
            r.bit_depth,
            r.channels,
            r.quality_tier,
            r.preferred_stream_format,
            r.status,
            r.room,
            r.display_name,
            r.is_active AS renderer_active,
            s.id AS stream_id,
            s.stream_key,
            s.stream_name,
            s.stream_format,
            s.audio_source_type,
            s.fifo_path,
            s.is_active AS stream_active
        FROM renderers r
        LEFT JOIN renderer_stream_map rsm ON r.id = rsm.renderer_id
        LEFT JOIN streams s ON rsm.stream_id = s.id
        WHERE r.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare renderer context query: ' . $conn->error);
    }

    $stmt->bind_param('i', $rendererId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Failed to execute renderer context query: ' . $error);
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function gee_get_selected_renderer_id(string $cookieName = 'gee_selected_renderer'): ?int
{
    if (!isset($_COOKIE[$cookieName])) {
        return null;
    }

    $rendererId = (int) $_COOKIE[$cookieName];

    return $rendererId > 0 ? $rendererId : null;
}

function gee_get_selected_renderer_context(string $cookieName = 'gee_selected_renderer'): ?array
{
    $rendererId = gee_get_selected_renderer_id($cookieName);

    if ($rendererId === null) {
        return null;
    }

    return gee_get_renderer_context($rendererId);
}

function gee_get_first_renderer_context(): ?array
{
    $conn = gee_db();

    $result = $conn->query("
        SELECT id
        FROM renderers
        WHERE COALESCE(is_active, 1) = 1
        ORDER BY COALESCE(display_name, hostname) ASC
        LIMIT 1
    ");

    if (!$result) {
        throw new RuntimeException('Failed to fetch first renderer: ' . $conn->error);
    }

    $row = $result->fetch_assoc();

    if (!$row) {
        return null;
    }

    return gee_get_renderer_context((int) $row['id']);
}

function gee_resolve_renderer_context(string $cookieName = 'gee_selected_renderer'): ?array
{
    $rendererContext = gee_get_selected_renderer_context($cookieName);

    if ($rendererContext === null) {
        $rendererContext = gee_get_first_renderer_context();
    }

    if ($rendererContext !== null) {
        $GLOBALS['gee_renderer_context'] = $rendererContext;
    }

    return $rendererContext;
}

function gee_get_renderer_display_name(?array $rendererContext): ?string
{
    if (!is_array($rendererContext)) {
        return null;
    }

    $displayName = trim((string) ($rendererContext['display_name'] ?? ''));
    if ($displayName !== '') {
        return $displayName;
    }

    $hostname = trim((string) ($rendererContext['hostname'] ?? ''));

    return $hostname !== '' ? $hostname : null;
}
function gee_get_last_renderer_cookie_name(): string
{
    return 'gee_last_renderer';
}

function gee_get_last_selected_renderer_id(): ?int
{
    $cookieName = gee_get_last_renderer_cookie_name();

    if (!isset($_COOKIE[$cookieName])) {
        return null;
    }

    $rendererId = (int) $_COOKIE[$cookieName];

    return $rendererId > 0 ? $rendererId : null;
}

function gee_set_last_selected_renderer_id(int $rendererId): bool
{
    if ($rendererId <= 0) {
        return false;
    }

    return setcookie(
        gee_get_last_renderer_cookie_name(),
        (string) $rendererId,
        [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]
    );
}