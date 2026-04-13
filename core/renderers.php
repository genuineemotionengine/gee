<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function gee_get_renderer_profile_path(string $rendererId): string
{
    return GEE_RENDERERS_DIR . '/' . $rendererId . '/profile.json';
}

function gee_get_registered_renderer_ids(): array
{
    if (!is_dir(GEE_RENDERERS_DIR)) {
        return [];
    }

    $entries = scandir(GEE_RENDERERS_DIR);
    if ($entries === false) {
        return [];
    }

    $ids = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = GEE_RENDERERS_DIR . '/' . $entry;
        if (!is_dir($path)) {
            continue;
        }

        $safeId = gee_safe_renderer_id($entry);
        if ($safeId !== '') {
            $ids[] = $safeId;
        }
    }

    sort($ids, SORT_NATURAL | SORT_FLAG_CASE);

    return $ids;
}

function gee_read_renderer_profile(string $rendererId): ?array
{
    $rendererId = gee_safe_renderer_id($rendererId);
    if ($rendererId === '') {
        return null;
    }

    $path = gee_get_renderer_profile_path($rendererId);
    if (!is_file($path)) {
        return null;
    }

    $json = file_get_contents($path);
    if ($json === false || trim($json) === '') {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function gee_build_renderer_context(string $rendererId, array $profile): array
{
    $hostname = trim((string)($profile['hostname'] ?? $rendererId));
    $displayName = trim((string)($profile['renderer_name'] ?? $hostname));

    return [
        'renderer_id' => $rendererId,
        'hostname' => $hostname,
        'display_name' => $displayName,
        'ip_address' => trim((string)($profile['ip_address'] ?? '')),
        'mac_address' => trim((string)($profile['mac_address'] ?? '')),
        'model' => trim((string)($profile['model'] ?? '')),
        'installer_version' => trim((string)($profile['installer_version'] ?? '1')),
    ];
}

function gee_get_renderer_context(string $rendererId): ?array
{
    $rendererId = gee_safe_renderer_id($rendererId);
    if ($rendererId === '') {
        return null;
    }

    $profile = gee_read_renderer_profile($rendererId);
    if ($profile === null) {
        return null;
    }

    return gee_build_renderer_context($rendererId, $profile);
}

function gee_get_all_renderer_contexts(): array
{
    $items = [];

    foreach (gee_get_registered_renderer_ids() as $rendererId) {
        $ctx = gee_get_renderer_context($rendererId);
        if ($ctx !== null) {
            $items[] = $ctx;
        }
    }

    usort($items, static function (array $a, array $b): int {
        return strcasecmp(
            (string)($a['display_name'] ?? $a['hostname'] ?? ''),
            (string)($b['display_name'] ?? $b['hostname'] ?? '')
        );
    });

    return $items;
}

function gee_get_selected_renderer_id(): ?string
{
    $raw = $_COOKIE[GEE_SELECTED_RENDERER_COOKIE] ?? null;
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $id = gee_safe_renderer_id($raw);
    return $id !== '' ? $id : null;
}

function gee_set_selected_renderer_cookie(string $rendererId): bool
{
    $rendererId = gee_safe_renderer_id($rendererId);
    if ($rendererId === '') {
        return false;
    }

    return setcookie(
        GEE_SELECTED_RENDERER_COOKIE,
        $rendererId,
        [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]
    );
}

function gee_get_selected_renderer_context(): ?array
{
    $rendererId = gee_get_selected_renderer_id();
    if ($rendererId === null) {
        return null;
    }

    return gee_get_renderer_context($rendererId);
}

function gee_get_first_renderer_context(): ?array
{
    $all = gee_get_all_renderer_contexts();
    return $all[0] ?? null;
}

function gee_get_selected_or_first_renderer_context(): ?array
{
    return gee_get_selected_renderer_context() ?? gee_get_first_renderer_context();
}