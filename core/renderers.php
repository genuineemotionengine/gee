<?php

declare(strict_types=1);

const GEE_RENDERERS_DIR = '/var/lib/gee-core/renderers';
const GEE_RENDERER_COOKIE = 'gee_selected_renderer';

function gee_get_renderer_cookie_name(): string
{
    return GEE_RENDERER_COOKIE;
}

function gee_get_renderers_base_dir(): string
{
    return GEE_RENDERERS_DIR;
}

function gee_safe_renderer_id(string $value): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
}

function gee_get_registered_renderer_ids(): array
{
    $baseDir = gee_get_renderers_base_dir();

    if (!is_dir($baseDir)) {
        return [];
    }

    $ids = [];
    $entries = scandir($baseDir);

    if ($entries === false) {
        return [];
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $fullPath = $baseDir . '/' . $entry;

        if (!is_dir($fullPath)) {
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

function gee_get_renderer_profile_path(string $rendererId): string
{
    return gee_get_renderers_base_dir() . '/' . $rendererId . '/profile.json';
}

function gee_read_renderer_profile(string $rendererId): ?array
{
    $rendererId = gee_safe_renderer_id($rendererId);

    if ($rendererId === '') {
        return null;
    }

    $profilePath = gee_get_renderer_profile_path($rendererId);

    if (!is_file($profilePath)) {
        return null;
    }

    $json = file_get_contents($profilePath);

    if ($json === false || trim($json) === '') {
        return null;
    }

    $data = json_decode($json, true);

    if (!is_array($data)) {
        return null;
    }

    return $data;
}

function gee_build_renderer_context(string $rendererId, array $profile): array
{
    $rendererId = gee_safe_renderer_id($rendererId);

    $hostname = trim((string)($profile['hostname'] ?? $rendererId));
    $displayName = trim((string)($profile['renderer_name'] ?? $hostname));
    $ip = trim((string)($profile['ip_address'] ?? ''));
    $model = trim((string)($profile['model'] ?? ''));
    $macAddress = trim((string)($profile['mac_address'] ?? ''));
    $installerVersion = trim((string)($profile['installer_version'] ?? '1'));

    return [
        'renderer_id' => $rendererId,
        'id' => $rendererId,
        'hostname' => $hostname,
        'display_name' => $displayName,
        'ip' => $ip,
        'ip_address' => $ip,
        'model' => $model,
        'mac_address' => $macAddress,
        'installer_version' => $installerVersion,

        // Compatibility placeholders for older UI/API expectations
        'room' => null,
        'status' => 'active',
        'is_active' => 1,
        'audio_profile' => 'safe',
        'alsa_device' => 'default',
        'max_sample_rate' => 44100,
        'bit_depth' => 16,
        'channels' => 2,
        'quality_tier' => 'standard',
        'preferred_stream_format' => '44100:16:2',
        'stream_id' => null,
        'stream_key' => 'safe',
        'stream_name' => 'Safe',
        'stream_format' => '44100:16:2',
        'fifo_path' => '/run/gee/snapfifo-' . $rendererId,
        'audio_source_type' => 'pipe',
        'stream_active' => 1,
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
    $contexts = [];

    foreach (gee_get_registered_renderer_ids() as $rendererId) {
        $context = gee_get_renderer_context($rendererId);

        if ($context !== null) {
            $contexts[] = $context;
        }
    }

    usort($contexts, static function (array $a, array $b): int {
        $aName = strtolower((string)($a['display_name'] ?? $a['hostname'] ?? ''));
        $bName = strtolower((string)($b['display_name'] ?? $b['hostname'] ?? ''));
        return $aName <=> $bName;
    });

    return $contexts;
}

function gee_get_selected_renderer_id_from_cookie(): ?string
{
    $cookieName = gee_get_renderer_cookie_name();

    if (empty($_COOKIE[$cookieName])) {
        return null;
    }

    $rendererId = gee_safe_renderer_id((string)$_COOKIE[$cookieName]);

    return $rendererId !== '' ? $rendererId : null;
}

function gee_set_selected_renderer_cookie(string $rendererId): bool
{
    $rendererId = gee_safe_renderer_id($rendererId);

    if ($rendererId === '') {
        return false;
    }

    return setcookie(
        gee_get_renderer_cookie_name(),
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
    $selectedRendererId = gee_get_selected_renderer_id_from_cookie();

    if ($selectedRendererId === null) {
        return null;
    }

    return gee_get_renderer_context($selectedRendererId);
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

function gee_renderer_exists(string $rendererId): bool
{
    return gee_get_renderer_context($rendererId) !== null;
}