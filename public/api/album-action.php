<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/renderers.php';
require_once __DIR__ . '/../../core/runtime.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

function gee_album_action_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function gee_album_action_fail(string $message, int $statusCode = 400, array $extra = []): void
{
    gee_album_action_json(array_merge([
        'status' => 'error',
        'message' => $message,
    ], $extra), $statusCode);
}

function gee_album_mpd_quote(string $value): string
{
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

function gee_album_mpd_command(array $runtime, string $command): array
{
    $host = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $port = (int)($runtime['mpd_port'] ?? 0);

    if ($port <= 0) {
        gee_album_action_fail('Invalid MPD port.', 500);
    }

    $errno = 0;
    $errstr = '';

    $fp = @fsockopen($host, $port, $errno, $errstr, 3.0);

    if (!is_resource($fp)) {
        gee_album_action_fail('Failed to connect to MPD.', 500, [
            'error' => $errstr,
            'errno' => $errno,
        ]);
    }

    stream_set_timeout($fp, 3, 0);

    fgets($fp);
    fwrite($fp, $command . "\n");

    $lines = [];

    while (!feof($fp)) {
        $line = trim((string)fgets($fp));

        if ($line === 'OK') {
            break;
        }

        if (str_starts_with($line, 'ACK')) {
            fclose($fp);
            gee_album_action_fail('MPD command failed.', 500, [
                'command' => $command,
                'mpd_error' => $line,
            ]);
        }

        if ($line !== '') {
            $lines[] = $line;
        }
    }

    fclose($fp);

    return $lines;
}

function gee_album_mpd_key_values(array $lines): array
{
    $data = [];

    foreach ($lines as $line) {
        $parts = explode(': ', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $data[strtolower(trim($parts[0]))] = trim($parts[1]);
    }

    return $data;
}

function gee_album_mpd_status(array $runtime): array
{
    return gee_album_mpd_key_values(gee_album_mpd_command($runtime, 'status'));
}

function gee_album_mpd_addid(array $runtime, string $path, ?int $position = null): int
{
    $command = 'addid ' . gee_album_mpd_quote($path);

    if ($position !== null && $position >= 0) {
        $command .= ' ' . $position;
    }

    $lines = gee_album_mpd_command($runtime, $command);
    $data = gee_album_mpd_key_values($lines);

    return isset($data['id']) ? (int)$data['id'] : 0;
}

function gee_album_insert_base_position(array $runtime, bool $stack): ?int
{
    $status = gee_album_mpd_status($runtime);

    $currentPos = isset($status['song']) ? (int)$status['song'] : -1;
    $playlistLength = isset($status['playlistlength']) ? (int)$status['playlistlength'] : 0;
    $songId = (string)($status['songid'] ?? '');

    if ($playlistLength <= 0 || $currentPos < 0) {
        return null;
    }

    $basePosition = $currentPos + 1;

    if (!$stack) {
        unset($_SESSION['gee_album_queue_stack']);
        return $basePosition;
    }

    $rendererId = (string)($runtime['renderer_id'] ?? '');
    $streamKey = (string)($runtime['stream_key'] ?? '');
    $stackKey = $rendererId . '|' . $streamKey . '|' . $songId . '|album';

    $sessionStack = $_SESSION['gee_album_queue_stack'] ?? null;

    if (
        !is_array($sessionStack)
        || ($sessionStack['key'] ?? '') !== $stackKey
        || !isset($sessionStack['last_position'])
    ) {
        $_SESSION['gee_album_queue_stack'] = [
            'key' => $stackKey,
            'last_position' => $basePosition,
        ];

        return $basePosition;
    }

    $nextPosition = (int)$sessionStack['last_position'] + 1;

    $_SESSION['gee_album_queue_stack'] = [
        'key' => $stackKey,
        'last_position' => $nextPosition,
    ];

    return $nextPosition;
}

function gee_get_album_tracks(string $album, string $albumartist): array
{
    if ($albumartist !== '') {
        $stmt = gee_db()->prepare("
            SELECT id, albumpath, track, title, artist, album, albumartist
            FROM app
            WHERE album = ?
              AND albumartist = ?
            ORDER BY CAST(track AS UNSIGNED), track, id
        ");

        if (!$stmt) {
            gee_album_action_fail('Failed to prepare album lookup.', 500);
        }

        $stmt->bind_param('ss', $album, $albumartist);
    } else {
        $stmt = gee_db()->prepare("
            SELECT id, albumpath, track, title, artist, album, albumartist
            FROM app
            WHERE album = ?
            ORDER BY CAST(track AS UNSIGNED), track, id
        ");

        if (!$stmt) {
            gee_album_action_fail('Failed to prepare album lookup.', 500);
        }

        $stmt->bind_param('s', $album);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $tracks = [];

    while ($row = $result->fetch_assoc()) {
        $path = trim((string)($row['albumpath'] ?? ''));

        if ($path === '') {
            continue;
        }

        $tracks[] = [
            'id' => (int)$row['id'],
            'path' => $path,
            'track' => (string)($row['track'] ?? ''),
            'title' => (string)($row['title'] ?? ''),
            'artist' => (string)($row['artist'] ?? ''),
            'album' => (string)($row['album'] ?? ''),
            'albumartist' => (string)($row['albumartist'] ?? ''),
        ];
    }

    $stmt->close();

    return $tracks;
}

$payload = json_decode((string)file_get_contents('php://input'), true);

if (!is_array($payload)) {
    gee_album_action_fail('Invalid JSON payload.');
}

$action = trim((string)($payload['action'] ?? ''));
$album = trim((string)($payload['album'] ?? ''));
$albumartist = trim((string)($payload['albumartist'] ?? ''));

if (!in_array($action, ['play_next', 'queue', 'play_now'], true)) {
    gee_album_action_fail('Invalid album action.', 400, [
        'action' => $action,
    ]);
}

if ($album === '') {
    gee_album_action_fail('Missing album.');
}

$runtime = gee_get_active_runtime();

if (!is_array($runtime)) {
    gee_album_action_fail('No active renderer runtime available.', 500);
}

$tracks = gee_get_album_tracks($album, $albumartist);

if (count($tracks) === 0) {
    gee_album_action_fail('No playable tracks found for album.', 404, [
        'album' => $album,
        'albumartist' => $albumartist,
    ]);
}

$addedIds = [];

if ($action === 'play_now') {
    unset($_SESSION['gee_album_queue_stack']);

    foreach ($tracks as $track) {
        $addedId = gee_album_mpd_addid($runtime, $track['path']);
        if ($addedId > 0) {
            $addedIds[] = $addedId;
        }
    }

    if (count($addedIds) === 0) {
        gee_album_action_fail('Failed to add album tracks.', 500);
    }

    gee_album_mpd_command($runtime, 'playid ' . $addedIds[0]);

    gee_album_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Album playing now.',
        'album' => $album,
        'albumartist' => $albumartist,
        'track_count' => count($tracks),
        'added_ids' => $addedIds,
    ]);
}

if ($action === 'play_next') {
    $position = gee_album_insert_base_position($runtime, false);

    foreach ($tracks as $index => $track) {
        $insertPosition = $position === null ? null : $position + $index;
        $addedId = gee_album_mpd_addid($runtime, $track['path'], $insertPosition);

        if ($addedId > 0) {
            $addedIds[] = $addedId;
        }
    }

    gee_album_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Album inserted next.',
        'album' => $album,
        'albumartist' => $albumartist,
        'track_count' => count($tracks),
        'added_ids' => $addedIds,
        'position' => $position,
    ]);
}

if ($action === 'queue') {
    $position = gee_album_insert_base_position($runtime, true);

    foreach ($tracks as $index => $track) {
        $insertPosition = $position === null ? null : $position + $index;
        $addedId = gee_album_mpd_addid($runtime, $track['path'], $insertPosition);

        if ($addedId > 0) {
            $addedIds[] = $addedId;
        }
    }

    if ($position !== null) {
        $_SESSION['gee_album_queue_stack']['last_position'] = $position + count($tracks) - 1;
    }

    gee_album_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Album queued.',
        'album' => $album,
        'albumartist' => $albumartist,
        'track_count' => count($tracks),
        'added_ids' => $addedIds,
        'position' => $position,
    ]);
}

gee_album_action_fail('Unhandled album action.', 500);