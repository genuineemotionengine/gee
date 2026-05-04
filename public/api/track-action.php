<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/renderers.php';
require_once __DIR__ . '/../../core/runtime.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

function gee_track_action_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function gee_track_action_fail(string $message, int $statusCode = 400, array $extra = []): void
{
    gee_track_action_json(array_merge([
        'status' => 'error',
        'message' => $message,
    ], $extra), $statusCode);
}

function gee_mpd_quote(string $value): string
{
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

function gee_mpd_command(array $runtime, string $command): array
{
    $host = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $port = (int)($runtime['mpd_port'] ?? 0);

    if ($port <= 0) {
        gee_track_action_fail('Invalid MPD port.', 500, [
            'mpd_host' => $host,
            'mpd_port' => $port,
        ]);
    }

    $errno = 0;
    $errstr = '';

    $fp = @fsockopen($host, $port, $errno, $errstr, 3.0);

    if (!is_resource($fp)) {
        gee_track_action_fail('Failed to connect to MPD.', 500, [
            'mpd_host' => $host,
            'mpd_port' => $port,
            'error' => $errstr,
            'errno' => $errno,
        ]);
    }

    stream_set_timeout($fp, 3, 0);

    fgets($fp); // MPD OK banner
    fwrite($fp, $command . "\n");

    $lines = [];

    while (!feof($fp)) {
        $line = trim((string)fgets($fp));

        if ($line === 'OK') {
            break;
        }

        if (str_starts_with($line, 'ACK')) {
            fclose($fp);
            gee_track_action_fail('MPD command failed.', 500, [
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

function gee_mpd_key_values(array $lines): array
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

function gee_mpd_status(array $runtime): array
{
    return gee_mpd_key_values(gee_mpd_command($runtime, 'status'));
}

function gee_mpd_addid(array $runtime, string $path, ?int $position = null): int
{
    $command = 'addid ' . gee_mpd_quote($path);

    if ($position !== null && $position >= 0) {
        $command .= ' ' . $position;
    }

    $lines = gee_mpd_command($runtime, $command);
    $data = gee_mpd_key_values($lines);

    $id = isset($data['id']) ? (int)$data['id'] : 0;

    if ($id <= 0) {
        gee_track_action_fail('MPD did not return an added track id.', 500, [
            'command' => $command,
            'response' => $lines,
        ]);
    }

    return $id;
}

function gee_get_track_by_id(int $trackId): array
{
    $stmt = gee_db()->prepare("
        SELECT id, albumpath, track, title, artist, album
        FROM app
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        gee_track_action_fail('Failed to prepare track lookup.', 500);
    }

    $stmt->bind_param('i', $trackId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    if (!$row) {
        gee_track_action_fail('Track not found.', 404, [
            'track_id' => $trackId,
        ]);
    }

    $path = trim((string)($row['albumpath'] ?? ''));

    if ($path === '') {
        gee_track_action_fail('Track has no playable path.', 500, [
            'track_id' => $trackId,
        ]);
    }

    return [
        'id' => (int)$row['id'],
        'path' => $path,
        'track' => (string)($row['track'] ?? ''),
        'title' => (string)($row['title'] ?? ''),
        'artist' => (string)($row['artist'] ?? ''),
        'album' => (string)($row['album'] ?? ''),
    ];
}

function gee_insert_position_after_current(array $runtime, bool $stack): ?int
{
    $status = gee_mpd_status($runtime);

    $currentPos = isset($status['song']) ? (int)$status['song'] : -1;
    $playlistLength = isset($status['playlistlength']) ? (int)$status['playlistlength'] : 0;
    $songId = (string)($status['songid'] ?? '');

    if ($playlistLength <= 0 || $currentPos < 0) {
        return null;
    }

    $basePosition = $currentPos + 1;

    if (!$stack) {
        unset($_SESSION['gee_queue_stack']);
        return $basePosition;
    }

    $rendererId = (string)($runtime['renderer_id'] ?? '');
    $streamKey = (string)($runtime['stream_key'] ?? '');
    $stackKey = $rendererId . '|' . $streamKey . '|' . $songId;

    $sessionStack = $_SESSION['gee_queue_stack'] ?? null;

    if (
        !is_array($sessionStack)
        || ($sessionStack['key'] ?? '') !== $stackKey
        || !isset($sessionStack['last_position'])
    ) {
        $_SESSION['gee_queue_stack'] = [
            'key' => $stackKey,
            'last_position' => $basePosition,
        ];

        return $basePosition;
    }

    $nextStackPosition = (int)$sessionStack['last_position'] + 1;

    $_SESSION['gee_queue_stack'] = [
        'key' => $stackKey,
        'last_position' => $nextStackPosition,
    ];

    return $nextStackPosition;
}

$raw = file_get_contents('php://input');
$payload = json_decode((string)$raw, true);

if (!is_array($payload)) {
    gee_track_action_fail('Invalid JSON payload.', 400);
}

$action = trim((string)($payload['action'] ?? ''));
$trackId = (int)($payload['track_id'] ?? 0);

if (!in_array($action, ['play_next', 'queue', 'play_now'], true)) {
    gee_track_action_fail('Invalid track action.', 400, [
        'action' => $action,
    ]);
}

if ($trackId <= 0) {
    gee_track_action_fail('Missing or invalid track_id.', 400);
}

$runtime = gee_get_active_runtime();

if (!is_array($runtime)) {
    gee_track_action_fail('No active renderer runtime available.', 500);
}

$track = gee_get_track_by_id($trackId);

if ($action === 'play_now') {
    unset($_SESSION['gee_queue_stack']);

    $addedId = gee_mpd_addid($runtime, $track['path']);
    gee_mpd_command($runtime, 'playid ' . $addedId);

    gee_track_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Track playing now.',
        'track' => $track,
        'mpd_id' => $addedId,
    ]);
}

if ($action === 'play_next') {
    $position = gee_insert_position_after_current($runtime, false);
    $addedId = gee_mpd_addid($runtime, $track['path'], $position);

    gee_track_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Track inserted next.',
        'track' => $track,
        'mpd_id' => $addedId,
        'position' => $position,
    ]);
}

if ($action === 'queue') {
    $position = gee_insert_position_after_current($runtime, true);
    $addedId = gee_mpd_addid($runtime, $track['path'], $position);

    gee_track_action_json([
        'status' => 'ok',
        'action' => $action,
        'message' => 'Track queued.',
        'track' => $track,
        'mpd_id' => $addedId,
        'position' => $position,
    ]);
}

gee_track_action_fail('Unhandled action.', 500);