<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/runtime.php';
require_once __DIR__ . '/getid3.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

function gee_get_query_string_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

function gee_get_query_string_string(string $key, string $default = ''): string
{
    if (!isset($_GET[$key])) {
        return $default;
    }

    $value = trim((string)$_GET[$key]);
    return $value !== '' ? $value : $default;
}

function gee_connect_mpd(array $runtime): MphpD
{
    try {
        $mphpd = new MphpD([
            'host' => (string)($runtime['mpd_host'] ?? '127.0.0.1'),
            'port' => (int)$runtime['mpd_port'],
            'timeout' => 5,
        ]);

        $mphpd->connect();

        return $mphpd;
    } catch (MPDException $e) {
        throw new RuntimeException('MPD connection failed: ' . $e->getMessage(), 0, $e);
    }
}

function gee_snapcast_request(string $method, array $params = []): ?array
{
    $payload = json_encode([
        'id' => random_int(1, 999999),
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params,
    ]);

    if ($payload === false) {
        return null;
    }

    $ch = curl_init('http://127.0.0.1:1780/jsonrpc');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 3,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function gee_snapcast_get_group_id_for_renderer(string $rendererHostname): ?string
{
    $rendererHostname = trim($rendererHostname);
    if ($rendererHostname === '') {
        return null;
    }

    $status = gee_snapcast_request('Server.GetStatus');

    if (!isset($status['result']['server']['groups']) || !is_array($status['result']['server']['groups'])) {
        return null;
    }

    foreach ($status['result']['server']['groups'] as $group) {
        if (!is_array($group) || !isset($group['clients']) || !is_array($group['clients'])) {
            continue;
        }

        foreach ($group['clients'] as $client) {
            $hostName = trim((string)($client['host']['name'] ?? ''));
            if ($hostName === $rendererHostname) {
                return isset($group['id']) ? (string)$group['id'] : null;
            }
        }
    }

    return null;
}

function gee_snapcast_set_renderer_stream(array $runtime): bool
{
    $rendererId = trim((string)($runtime['renderer_id'] ?? ''));
    $rendererHostname = trim((string)($runtime['hostname'] ?? ''));
    $streamKey = trim((string)($runtime['stream_key'] ?? ''));

    if ($rendererId === '' || $rendererHostname === '' || $streamKey === '') {
        return false;
    }

    $groupId = gee_snapcast_get_group_id_for_renderer($rendererHostname);
    if ($groupId === null || $groupId === '') {
        return false;
    }

    $streamId = $rendererId . '-' . $streamKey;

    $result = gee_snapcast_request('Group.SetStream', [
        'id' => $groupId,
        'stream_id' => $streamId,
    ]);

    return is_array($result) && !isset($result['error']);
}

function gee_build_and_load_playlist(array $runtime, string $sql): array
{
    $conn = gee_db();

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Failed to prepare playlist query: ' . $conn->error);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Failed to execute playlist query: ' . $error);
    }

    $result = $stmt->get_result();
    $tracks = [];

    while ($row = $result->fetch_assoc()) {
        $track = trim((string)($row['albumpath'] ?? ''));
        if ($track !== '') {
            $tracks[] = $track;
        }
    }

    $stmt->close();

    if ($tracks === []) {
        throw new RuntimeException('No tracks found for playlist generation.');
    }

    shuffle($tracks);

    $playlistDirectory = (string)($runtime['playlist_directory'] ?? '');
    $playlistPath = (string)($runtime['playlist_path'] ?? '');

    if ($playlistDirectory === '' || $playlistPath === '') {
        throw new RuntimeException('Renderer runtime playlist paths are incomplete.');
    }

    if (!is_dir($playlistDirectory) && !mkdir($playlistDirectory, 0775, true) && !is_dir($playlistDirectory)) {
        throw new RuntimeException('Unable to create playlist directory: ' . $playlistDirectory);
    }

    $playlistText = implode("\n", $tracks) . "\n";

    if (file_put_contents($playlistPath, $playlistText) === false) {
        throw new RuntimeException('Unable to write playlist file: ' . $playlistPath);
    }

    $mphpd = gee_connect_mpd($runtime);

    $mphpd->queue()->clear();

    foreach ($tracks as $track) {
        $mphpd->queue()->add($track);
    }

    $mphpd->player()->repeat(1);
    $mphpd->player()->play(0);

    return [
        'playlist_path' => $playlistPath,
        'track_count' => count($tracks),
    ];
}

function gee_renderer_summary(array $rendererContext): array
{
    return [
        'renderer_id' => (string)($rendererContext['renderer_id'] ?? ''),
        'renderer_name' => (string)($rendererContext['renderer_name'] ?? ''),
        'display_name' => (string)($rendererContext['display_name'] ?? ''),
        'hostname' => (string)($rendererContext['hostname'] ?? ''),
        'ip_address' => (string)($rendererContext['ip_address'] ?? ''),
        'mac_address' => (string)($rendererContext['mac_address'] ?? ''),
        'model' => (string)($rendererContext['model'] ?? ''),
        'installer_version' => (string)($rendererContext['installer_version'] ?? ''),
    ];
}

function gee_renderer_runtime_summary(array $runtime): array
{
    return [
        'renderer_id' => (string)($runtime['renderer_id'] ?? ''),
        'renderer_name' => (string)($runtime['renderer_name'] ?? ''),
        'display_name' => (string)($runtime['display_name'] ?? ''),
        'hostname' => (string)($runtime['hostname'] ?? ''),
        'ip_address' => (string)($runtime['ip_address'] ?? ''),
        'config_version' => (string)($runtime['config_version'] ?? ''),

        'runtime_ready' => (bool)($runtime['runtime_ready'] ?? false),
        'renderer_dir' => (string)($runtime['renderer_dir'] ?? ''),
        'runtime_dir' => (string)($runtime['runtime_dir'] ?? ''),
        'runtime_conf_path' => (string)($runtime['runtime_conf_path'] ?? ''),
        'canonical_conf_path' => (string)($runtime['canonical_conf_path'] ?? ''),

        'music_directory' => (string)($runtime['music_directory'] ?? ''),
        'playlist_directory' => (string)($runtime['playlist_directory'] ?? ''),
        'playlist_filename' => (string)($runtime['playlist_filename'] ?? ''),
        'playlist_path' => (string)($runtime['playlist_path'] ?? ''),

        'db_file' => (string)($runtime['db_file'] ?? ''),
        'log_file' => (string)($runtime['log_file'] ?? ''),
        'state_file' => (string)($runtime['state_file'] ?? ''),
        'sticker_file' => (string)($runtime['sticker_file'] ?? ''),

        'active_stream' => (string)($runtime['active_stream'] ?? ''),
        'stream_key' => (string)($runtime['stream_key'] ?? ''),
        'stream_name' => (string)($runtime['stream_name'] ?? ''),
        'stream_format' => (string)($runtime['stream_format'] ?? ''),
        'fifo_path' => (string)($runtime['fifo_path'] ?? ''),

        'bind_to_address' => (string)($runtime['bind_to_address'] ?? ''),
        'mpd_host' => (string)($runtime['mpd_host'] ?? ''),
        'mpd_port' => (int)($runtime['mpd_port'] ?? 0),
        'mpd_port_source' => (string)($runtime['mpd_port_source'] ?? ''),
    ];
}

function gee_get_selected_runtime_or_fail(): array
{
    $runtime = gee_get_active_runtime();

    if (!is_array($runtime)) {
        gee_fail('No active renderer runtime available.', 500);
    }

    return $runtime;
}

$service = gee_get_query_string_int('service');
$mod = gee_get_query_string_int('mod');
$rendererId = gee_safe_renderer_id(gee_get_query_string_string('renderer_id'));
$streamKey = trim((string)($_GET['stream'] ?? ''));

/*
|--------------------------------------------------------------------------
| Renderer services
|--------------------------------------------------------------------------
*/

if ($service === 20) {
    $allRenderers = gee_get_all_renderer_contexts();
    $selectedRendererId = gee_get_selected_renderer_id();

    $items = [];

    foreach ($allRenderers as $rendererContext) {
        $summary = gee_renderer_summary($rendererContext);
        $runtime = gee_get_renderer_runtime_context($rendererContext);

        $summary['selected'] = $selectedRendererId !== null
            ? ($summary['renderer_id'] === $selectedRendererId)
            : false;

        $summary['runtime_ready'] = is_array($runtime) ? (bool)($runtime['runtime_ready'] ?? false) : false;
        $summary['config_version'] = is_array($runtime) ? (string)($runtime['config_version'] ?? '') : '';

        $items[] = $summary;
    }

    gee_json_response([
        'status' => 'ok',
        'count' => count($items),
        'selected_renderer_id' => $selectedRendererId,
        'selected_stream' => gee_get_selected_stream(),
        'renderers' => $items,
    ]);
}

if ($service === 21) {
    if ($rendererId === '') {
        gee_fail('Missing renderer_id.', 400);
    }

    $rendererContext = gee_get_renderer_context($rendererId);
    if (!is_array($rendererContext)) {
        gee_fail('Renderer not found.', 404, ['renderer_id' => $rendererId]);
    }

    if (!gee_set_selected_renderer_cookie($rendererId)) {
        gee_fail('Failed to set renderer selection cookie.', 500, [
            'renderer_id' => $rendererId,
        ]);
    }

    $runtime = gee_get_renderer_runtime_context($rendererContext);
    if (!is_array($runtime)) {
        gee_fail('Renderer runtime context unavailable.', 500, ['renderer_id' => $rendererId]);
    }

    $runtime = gee_get_renderer_stream_runtime($runtime);

    $snapcastSwitched = gee_snapcast_set_renderer_stream($runtime);

    gee_json_response([
        'status' => 'ok',
        'message' => 'Renderer selected.',
        'selected_renderer_id' => $rendererId,
        'selected_stream' => gee_get_selected_stream(),
        'snapcast_stream_switched' => $snapcastSwitched,
        'renderer' => gee_renderer_summary($rendererContext),
        'runtime' => gee_renderer_runtime_summary($runtime),
    ]);
}

if ($service === 22) {
    $runtime = gee_get_selected_runtime_or_fail();

    gee_json_response([
        'status' => 'ok',
        'selected_renderer_id' => (string)($runtime['renderer_id'] ?? ''),
        'selected_stream' => (string)($runtime['stream_key'] ?? 'safe'),
        'runtime' => gee_renderer_runtime_summary($runtime),
    ]);
}

if ($service === 23) {
    if (!gee_is_valid_stream_key($streamKey)) {
        gee_fail('Invalid stream. Use safe or hires.', 400);
    }

    if (!gee_set_selected_stream_cookie($streamKey)) {
        gee_fail('Failed to set selected stream cookie.', 500);
    }

    $runtime = gee_get_selected_runtime_or_fail();
    $runtime = gee_get_renderer_stream_runtime($runtime, $streamKey);

    $snapcastSwitched = gee_snapcast_set_renderer_stream($runtime);

    gee_json_response([
        'status' => 'ok',
        'message' => 'Stream selected.',
        'selected_stream' => $streamKey,
        'snapcast_stream_switched' => $snapcastSwitched,
        'runtime' => gee_renderer_runtime_summary($runtime),
    ]);
}

if ($service === 24) {
    $runtime = gee_get_selected_runtime_or_fail();

    gee_json_response([
        'status' => 'ok',
        'selected_renderer_id' => (string)($runtime['renderer_id'] ?? ''),
        'selected_stream' => (string)($runtime['stream_key'] ?? 'safe'),
        'runtime' => gee_renderer_runtime_summary($runtime),
    ]);
}

/*
|--------------------------------------------------------------------------
| Playback services
|--------------------------------------------------------------------------
*/

$runtime = gee_get_selected_runtime_or_fail();

try {
    $mphpd = gee_connect_mpd($runtime);
} catch (RuntimeException $e) {
    gee_fail($e->getMessage(), 500, [
        'renderer_id' => (string)($runtime['renderer_id'] ?? ''),
        'stream_key' => (string)($runtime['stream_key'] ?? ''),
        'mpd_host' => (string)($runtime['mpd_host'] ?? ''),
        'mpd_port' => (int)($runtime['mpd_port'] ?? 0),
        'mpd_port_source' => (string)($runtime['mpd_port_source'] ?? ''),
    ]);
}

if ($service === 1) {
    $status = $mphpd->status();
    $song = $mphpd->player()->current_song();

    $elapsed = (string)($status['elapsed'] ?? '0');
    $duration = (string)($status['duration'] ?? '0');

    $elapsed = explode('.', $elapsed)[0] ?? '0';
    $duration = explode('.', $duration)[0] ?? '0';

    $title = (string)($song['title'] ?? '');
    $artist = (string)($song['artist'] ?? '');
    $album = (string)($song['album'] ?? '');
    $albumartist = (string)($song['albumartist'] ?? '');
    $file = (string)($song['file'] ?? '');

    if (($title === '' || $artist === '' || $album === '' || $albumartist === '') && $file !== '') {
        $stmt = gee_db()->prepare("
            SELECT title, artist, album, albumartist
            FROM app
            WHERE albumpath = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param('s', $file);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                $title = $title !== '' ? $title : (string)($row['title'] ?? '');
                $artist = $artist !== '' ? $artist : (string)($row['artist'] ?? '');
                $album = $album !== '' ? $album : (string)($row['album'] ?? '');
                $albumartist = $albumartist !== '' ? $albumartist : (string)($row['albumartist'] ?? '');
            }
        }
    }

    $image = null;
    $fullPath = $file !== '' ? GEE_MUSIC_ROOT . '/' . ltrim($file, '/') : '';

    if ($fullPath !== '' && is_file($fullPath)) {
        $getID3 = new getID3();
        $info = $getID3->analyze($fullPath);

        if (isset($info['comments']['picture'][0])) {
            $image = 'data:' .
                $info['comments']['picture'][0]['image_mime'] .
                ';charset=utf-8;base64,' .
                base64_encode($info['comments']['picture'][0]['data']);
        }
    }

    gee_json_response([
        'status' => 'ok',
        'renderer_id' => (string)($runtime['renderer_id'] ?? ''),
        'renderer_name' => (string)($runtime['renderer_name'] ?? ''),
        'renderer_display' => strtoupper((string)($runtime['display_name'] ?? '')),
        'active_stream' => (string)($runtime['active_stream'] ?? ''),
        'stream_key' => (string)($runtime['stream_key'] ?? ''),
        'stream_format' => (string)($runtime['stream_format'] ?? ''),
        'image' => $image,
        'title' => $title,
        'artist' => $artist,
        'album' => $album,
        'albumartist' => $albumartist,
        'elapsed' => (int)$elapsed,
        'duration' => (int)$duration,
        'volume' => (int)($status['volume'] ?? 0),
        'state' => (string)($status['state'] ?? 'stop'),
    ]);
}

if ($service === 2) {
    $mphpd->player()->pause();
    gee_json_response(['status' => 'ok']);
}

if ($service === 3) {
    $status = $mphpd->status();
    $pauseState = (string)($status['state'] ?? '');

    $mphpd->player()->previous();

    if ($pauseState === 'pause') {
        $mphpd->player()->pause();
    }

    gee_json_response(['status' => 'ok']);
}

if ($service === 4) {
    $status = $mphpd->status();
    $pauseState = (string)($status['state'] ?? '');

    $mphpd->player()->next();

    if ($pauseState === 'pause') {
        $mphpd->player()->pause();
    }

    gee_json_response(['status' => 'ok']);
}

if ($service === 5) {
    try {
        $playlist = gee_build_and_load_playlist(
            $runtime,
            "SELECT albumpath FROM app WHERE genre != 'Relaxation'"
        );

        gee_json_response([
            'status' => 'ok',
            'message' => 'Music loaded',
            'track_count' => $playlist['track_count'],
            'playlist_path' => $playlist['playlist_path'],
        ]);
    } catch (Throwable $e) {
        gee_fail('Playlist load failed: ' . $e->getMessage(), 500, [
            'renderer_id' => (string)($runtime['renderer_id'] ?? ''),
            'stream_key' => (string)($runtime['stream_key'] ?? ''),
            'playlist_path' => (string)($runtime['playlist_path'] ?? ''),
        ]);
    }
}

if ($service === 13) {
    $song = $mphpd->player()->current_song();
    $status = $mphpd->status();
    $pauseState = (string)($status['state'] ?? '');
    $pos = isset($song['pos']) ? (int)$song['pos'] : 0;

    $mphpd->player()->play($pos);

    if ($pauseState === 'pause') {
        $mphpd->player()->pause();
    }

    gee_json_response(['status' => 'ok']);
}

if ($service === 15) {
    $mphpd->player()->volume($mod);
    gee_json_response([
        'status' => 'ok',
        'mod' => $mod,
    ]);
}

gee_fail('Unknown or unsupported service.', 400, ['service' => $service]);