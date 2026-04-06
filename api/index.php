<?php

declare(strict_types=1);

require_once '/var/www/app/core/renderers.php';
require_once '/var/www/app/core/streams.php';
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/getid3.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

parse_str($_SERVER['QUERY_STRING'] ?? '', $qsarray);

$service = isset($qsarray['service']) ? (int) $qsarray['service'] : 0;
$id = isset($qsarray['id']) ? (int) $qsarray['id'] : 0;
$mod = isset($qsarray['mod']) ? (int) $qsarray['mod'] : 0;
$verbose = isset($qsarray['verbose']) ? (int) $qsarray['verbose'] : 0;
$plnext = isset($qsarray['plnext']) ? (int) $qsarray['plnext'] : 0;

$conn = gee_db();
$streamContext = gee_resolve_stream_context();

if (!is_array($streamContext)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No renderer or stream context could be resolved']);
    exit;
}

$GLOBALS['gee_renderer_context'] = $streamContext;

$mphpd = new MphpD([
    'host' => gee_get_mpd_host_from_stream($streamContext),
    'port' => gee_get_mpd_port_from_stream($streamContext),
    'timeout' => 5,
]);

try {
    $mphpd->connect();
} catch (MPDException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Failed to connect to MPD',
        'message' => $e->getMessage(),
        'renderer' => gee_get_renderer_display_name($streamContext),
        'stream_key' => $streamContext['stream_key'] ?? null,
        'mpd_host' => gee_get_mpd_host_from_stream($streamContext),
        'mpd_port' => gee_get_mpd_port_from_stream($streamContext),
    ]);
    exit;
}

function gee_api_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

switch ($service) {
    case 1:
        $playlistPath = gee_get_playlist_path_from_stream($streamContext);
        if (!is_file($playlistPath)) {
            $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
            ob_start();
            include __DIR__ . '/loadplaylist.php';
            ob_end_clean();
        }
        include __DIR__ . '/getmeta.php';
        exit;

    case 2:
        $mphpd->player()->pause();
        include __DIR__ . '/getmeta.php';
        exit;

    case 3:
        $status = $mphpd->status();
        $wasPaused = (($status['state'] ?? '') === 'pause');
        $mphpd->player()->previous();
        if ($wasPaused) {
            $mphpd->player()->pause();
        }
        include __DIR__ . '/getmeta.php';
        exit;

    case 4:
        $status = $mphpd->status();
        $wasPaused = (($status['state'] ?? '') === 'pause');
        $mphpd->player()->next();
        if ($wasPaused) {
            $mphpd->player()->pause();
        }
        include __DIR__ . '/getmeta.php';
        exit;

    case 5:
        $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
        ob_start();
        include __DIR__ . '/loadplaylist.php';
        ob_end_clean();
        include __DIR__ . '/getmeta.php';
        exit;

    case 6:
        $sql = "SELECT albumpath FROM app WHERE genre = 'Classical'";
        ob_start();
        include __DIR__ . '/loadplaylist.php';
        ob_end_clean();
        include __DIR__ . '/getmeta.php';
        exit;

    case 7:
        $sql = "SELECT albumpath FROM app WHERE genre = 'Relaxation' OR genre = 'Ambient' OR genre = 'Chilled Electronic'";
        ob_start();
        include __DIR__ . '/loadplaylist.php';
        ob_end_clean();
        include __DIR__ . '/getmeta.php';
        exit;

    case 8:
        include __DIR__ . '/getalbum.php';
        gee_api_json($albumtracks ?? []);
        break;

    case 12:
        $currentSong = $mphpd->player()->current_song();

        $stmt = $conn->prepare('SELECT albumpath, title, artist FROM app WHERE id = ? LIMIT 1');
        if (!$stmt) {
            gee_api_json(['error' => 'Failed to prepare track query: ' . $conn->error], 500);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            gee_api_json(['error' => 'Track not found', 'id' => $id], 404);
        }

        $mphpd->queue()->add_id((string) $row['albumpath'], '+0');

        if ($plnext) {
            $mphpd->player()->next();
        }

        include __DIR__ . '/getmeta.php';
        exit;

    case 13:
        $currentSong = $mphpd->player()->current_song();
        $status = $mphpd->status();
        $wasPaused = (($status['state'] ?? '') === 'pause');
        $pos = isset($currentSong['pos']) ? (int) $currentSong['pos'] : 0;
        $mphpd->player()->play($pos);
        if ($wasPaused) {
            $mphpd->player()->pause();
        }
        include __DIR__ . '/getmeta.php';
        exit;

    case 15:
        $mphpd->player()->volume($mod);
        gee_api_json([
            'status' => 'ok',
            'mod' => $mod,
            'renderer' => gee_get_renderer_display_name($streamContext),
            'stream_key' => $streamContext['stream_key'] ?? null,
        ]);
        break;

    default:
        gee_api_json([
            'error' => 'Unsupported service',
            'service' => $service,
            'supported_services' => [1, 2, 3, 4, 5, 6, 7, 8, 12, 13, 15],
        ], 400);
}
