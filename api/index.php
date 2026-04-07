<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/renderer_runtime.php';
require_once __DIR__ . '/../core/renderer_sessions.php';
require_once __DIR__ . '/getid3.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

function gee_normalize_stream_key_to_active_stream(?string $streamKey): ?string
{
    $streamKey = strtolower(trim((string)$streamKey));

    return match ($streamKey) {
        'stream_safe', 'safe' => 'safe',
        'stream_hires', 'hires' => 'hires',
        default => null,
    };
}

parse_str($_SERVER['QUERY_STRING'] ?? '', $qsarray);

$service = isset($qsarray['service']) ? (int) $qsarray['service'] : 0;
$id = isset($qsarray['id']) ? (int) $qsarray['id'] : 0;
$mod = isset($qsarray['mod']) ? (int) $qsarray['mod'] : 0;
$verbose = !empty($qsarray['verbose']);
$plnext = !empty($qsarray['plnext']);

$conn = gee_db();

/*
|--------------------------------------------------------------------------
| Resolve renderer context
|--------------------------------------------------------------------------
*/
$rendererContext = gee_get_selected_renderer_context();

if ($rendererContext === null) {
    $rendererContext = gee_get_first_renderer_context();
}

if ($rendererContext === null) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'No renderer context available.'
    ]);
    exit;
}

$rendererId = (int)($rendererContext['renderer_id'] ?? $rendererContext['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Sync session stream from renderer-context row
|--------------------------------------------------------------------------
|
| The renderer context page is currently where stream changes happen.
| The player itself only polls service=1, so we reconcile the session here.
|
*/
$sessionBeforeSync = gee_get_renderer_session_for_context($rendererContext);
$sessionStreamBeforeSync = is_array($sessionBeforeSync) ? (string)($sessionBeforeSync['active_stream'] ?? '') : '';
$contextStream = gee_normalize_stream_key_to_active_stream($rendererContext['stream_key'] ?? null);
$streamChangedByContext = false;

if ($rendererId > 0 && $contextStream !== null && $sessionStreamBeforeSync !== $contextStream) {
    gee_set_renderer_active_stream($rendererId, $contextStream);
    $streamChangedByContext = true;
}

/*
|--------------------------------------------------------------------------
| Resolve renderer-first runtime context
|--------------------------------------------------------------------------
*/
$geeRuntimeContext = gee_get_renderer_runtime_context($rendererContext);

if ($geeRuntimeContext === null) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to resolve renderer runtime context.'
    ]);
    exit;
}

$GLOBALS['gee_renderer_context'] = $rendererContext;
$GLOBALS['gee_runtime_context'] = $geeRuntimeContext;

$mpdHost = (string) $geeRuntimeContext['mpd_host'];
$mpdPort = (int) $geeRuntimeContext['mpd_port'];

$mphpd = new MphpD([
    'host' => $mpdHost,
    'port' => $mpdPort,
    'timeout' => 5,
]);

try {
    $mphpd->connect();
} catch (MPDException $e) {
    http_response_code(500);
    echo 'MPD connection failed: ' . $e->getMessage();
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 1 - Get Meta
|--------------------------------------------------------------------------
*/
if ($service === 1) {
    $lastRendererId = gee_get_last_selected_renderer_id();
    $rendererChanged = ($rendererId > 0 && $lastRendererId !== $rendererId);

    /*
    |--------------------------------------------------------------------------
    | If renderer changed or stream changed in renderer context, restore the
    | renderer session into the correct MPD before returning metadata.
    |--------------------------------------------------------------------------
    */
    if (($rendererChanged || $streamChangedByContext) && $rendererId > 0) {
        try {
            gee_restore_renderer_session_to_mpd($rendererContext);
            gee_set_last_selected_renderer_id($rendererId);

            $geeRuntimeContext = gee_get_renderer_runtime_context($rendererContext);
            $GLOBALS['gee_runtime_context'] = $geeRuntimeContext;

            $mpdHost = (string)($geeRuntimeContext['mpd_host'] ?? '127.0.0.1');
            $mpdPort = (int)($geeRuntimeContext['mpd_port'] ?? 6601);

            $mphpd = new MphpD([
                'host' => $mpdHost,
                'port' => $mpdPort,
                'timeout' => 5,
            ]);

            $mphpd->connect();
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Renderer restore failed.',
                'details' => $e->getMessage(),
            ]);
            exit;
        }
    }

    $playlistPath = (string)($geeRuntimeContext['playlist_path'] ?? '');

    if ($playlistPath !== '' && !is_file($playlistPath)) {
        $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
        include __DIR__ . '/loadplaylist.php';
    }

    try {
        gee_capture_renderer_session_from_mpd($rendererContext);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Renderer session capture failed.',
            'details' => $e->getMessage(),
        ]);
        exit;
    }

    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 2 - Pause / Play toggle
|--------------------------------------------------------------------------
*/
if ($service === 2) {
    $mphpd->player()->pause();
    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 3 - Previous
|--------------------------------------------------------------------------
*/
if ($service === 3) {
    $statusArray = $mphpd->status();
    $pauseStatus = $statusArray['state'] ?? null;

    $mphpd->player()->previous();

    if ($pauseStatus === 'pause') {
        $mphpd->player()->pause();
    }

    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 4 - Next
|--------------------------------------------------------------------------
*/
if ($service === 4) {
    $statusArray = $mphpd->status();
    $pauseStatus = $statusArray['state'] ?? null;

    $mphpd->player()->next();

    if ($pauseStatus === 'pause') {
        $mphpd->player()->pause();
    }

    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 5 - Restart Playlist
|--------------------------------------------------------------------------
*/
if ($service === 5) {
    $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
    include __DIR__ . '/loadplaylist.php';
    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 6 - Classical Playlist
|--------------------------------------------------------------------------
*/
if ($service === 6) {
    $sql = "SELECT albumpath FROM app WHERE genre = 'Classical'";
    include __DIR__ . '/loadplaylist.php';
    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 7 - Relaxation Playlist
|--------------------------------------------------------------------------
*/
if ($service === 7) {
    $sql = "SELECT albumpath FROM app WHERE genre = 'Relaxation' OR genre = 'Ambient' OR genre = 'Chilled Electronic'";
    include __DIR__ . '/loadplaylist.php';
    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 8 - Album List
|--------------------------------------------------------------------------
*/
if ($service === 8) {
    include __DIR__ . '/getalbum.php';
    echo json_encode($albumtracks ?? []);
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 12 - Play Next
|--------------------------------------------------------------------------
*/
if ($service === 12) {
    $currentSong = $mphpd->player()->current_song();

    if ($verbose) {
        echo "Current Song";
        echo '<pre>' . htmlentities(print_r($currentSong, true), ENT_SUBSTITUTE) . '</pre>';
        echo "<br><br><br>";
    }

    $stmt = $conn->prepare("SELECT albumpath, title, artist FROM app WHERE id = ?");

    if (!$stmt) {
        http_response_code(500);
        echo 'Prepare failed: ' . $conn->error;
        exit;
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        http_response_code(500);
        echo 'Execute failed: ' . $error;
        exit;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo 'Track not found.';
        exit;
    }

    $uri = $row['albumpath'];
    $title = $row['title'];
    $artist = $row['artist'];

    $pos = '+0';

    if ($verbose) {
        echo "uri: " . htmlentities((string) $uri, ENT_SUBSTITUTE) . "<br>";
        echo "title: " . htmlentities((string) $title, ENT_SUBSTITUTE) . "<br>";
        echo "artist: " . htmlentities((string) $artist, ENT_SUBSTITUTE) . "<br>";
        echo "pos: " . htmlentities((string) $pos, ENT_SUBSTITUTE) . "<br><br>";
    }

    $results = $mphpd->queue()->add_id($uri, $pos);

    if ($verbose) {
        echo "Playlist Add:<br>";
        echo '<pre>' . htmlentities(print_r($results, true), ENT_SUBSTITUTE) . '</pre>';
    }

    if ($plnext) {
        $mphpd->player()->next();
        gee_capture_renderer_session_from_mpd($rendererContext);
        include __DIR__ . '/getmeta.php';
        exit;
    }

    gee_capture_renderer_session_from_mpd($rendererContext);

    echo json_encode([
        'status' => 'ok',
        'uri' => $uri,
        'title' => $title,
        'artist' => $artist,
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 13 - Restart Current Track
|--------------------------------------------------------------------------
*/
if ($service === 13) {
    $currentArray = $mphpd->player()->current_song();

    if ($verbose) {
        echo "Current Song";
        echo '<pre>' . htmlentities(print_r($currentArray, true), ENT_SUBSTITUTE) . '</pre>';
        echo "<br><br><br>";
    }

    $statusArray = $mphpd->status();

    if ($verbose) {
        echo "Status";
        echo '<pre>' . htmlentities(print_r($statusArray, true), ENT_SUBSTITUTE) . '</pre>';
        echo "<br><br><br>";
    }

    $pauseStatus = $statusArray['state'] ?? null;
    $pos = isset($currentArray['pos']) ? (int) $currentArray['pos'] : 0;

    $mphpd->player()->play($pos);

    if ($pauseStatus === 'pause') {
        $mphpd->player()->pause();
    }

    gee_capture_renderer_session_from_mpd($rendererContext);
    include __DIR__ . '/getmeta.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Service 15 - Volume adjust
|--------------------------------------------------------------------------
*/
if ($service === 15) {
    $mphpd->player()->volume($mod);

    echo json_encode([
        'status' => 'ok',
        'mod' => $mod,
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Unknown service
|--------------------------------------------------------------------------
*/
http_response_code(400);
echo json_encode([
    'status' => 'error',
    'message' => 'Unknown or unsupported service.',
    'service' => $service,
]);