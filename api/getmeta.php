<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderer_runtime.php';

$geeRuntimeContext = $GLOBALS['gee_runtime_context'] ?? null;

if (!is_array($geeRuntimeContext)) {
    $geeRuntimeContext = gee_get_renderer_runtime_context();
}

$geeRendererName = null;
$geeActiveStream = null;
$geeAllowedStreams = [];
$geeStreamKey = null;
$geeStreamFormat = null;
$geeMpdHost = '127.0.0.1';
$geeMpdPort = 6601;

if (is_array($geeRuntimeContext)) {
    $geeRendererName = $geeRuntimeContext['display_name'] ?: ($geeRuntimeContext['hostname'] ?? null);
    $geeActiveStream = $geeRuntimeContext['active_stream'] ?? null;
    $geeAllowedStreams = $geeRuntimeContext['allowed_streams'] ?? [];
    $geeStreamKey = $geeRuntimeContext['stream_key'] ?? null;
    $geeStreamFormat = $geeRuntimeContext['stream_format'] ?? null;
    $geeMpdHost = (string)($geeRuntimeContext['mpd_host'] ?? '127.0.0.1');
    $geeMpdPort = (int)($geeRuntimeContext['mpd_port'] ?? 6601);
}

$statusarray = $mphpd->status();

if (!is_array($statusarray)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'MPD status() did not return an array.',
        'renderer' => $geeRendererName,
        'active_stream' => $geeActiveStream,
        'stream_key' => $geeStreamKey,
        'stream_format' => $geeStreamFormat,
    ]);
    exit;
}

$elapsedRaw = (string)($statusarray['elapsed'] ?? '0');
$durationRaw = (string)($statusarray['duration'] ?? '0');
$volume = (int)($statusarray['volume'] ?? 0);
$state = (string)($statusarray['state'] ?? 'stop');

$elapsedParts = explode('.', $elapsedRaw);
$durationParts = explode('.', $durationRaw);

$elapsed = $elapsedParts[0] ?? '0';
$duration = $durationParts[0] ?? '0';

$mySimpleArray = $mphpd->player()->current_song();

if (!is_array($mySimpleArray) || empty($mySimpleArray['file'])) {
    echo json_encode([
        'image' => null,
        'title' => '',
        'artist' => '',
        'album' => '',
        'elapsed' => $elapsed,
        'duration' => $duration,
        'albumartist' => '',
        'volume' => $volume,
        'nexttitle' => '',
        'nextartist' => '',
        'state' => $state,
        'renderer' => $geeRendererName,
        'active_stream' => $geeActiveStream,
        'allowed_streams' => $geeAllowedStreams,
        'stream_key' => $geeStreamKey,
        'stream_format' => $geeStreamFormat,
        'message' => 'No current song loaded'
    ]);
    exit;
}

$flacfileRel = (string)$mySimpleArray['file'];
$album = (string)($mySimpleArray['album'] ?? '');
$artist = (string)($mySimpleArray['artist'] ?? '');
$title = (string)($mySimpleArray['title'] ?? '');
$albumartist = (string)($mySimpleArray['albumartist'] ?? '');

if (stripos($albumartist, 'Various Artists - ') === 0) {
    $albumartist = 'Various Artists';
}

/*
|--------------------------------------------------------------------------
| Fallback to DB metadata if MPD tags are sparse
|--------------------------------------------------------------------------
*/
if ($title === '' || $artist === '' || $album === '' || $albumartist === '') {
    $conn = gee_db();
    $stmt = $conn->prepare("
        SELECT title, artist, album, albumartist
        FROM app
        WHERE albumpath = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param('s', $flacfileRel);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;

            if ($row) {
                $title = $title !== '' ? $title : (string)($row['title'] ?? '');
                $artist = $artist !== '' ? $artist : (string)($row['artist'] ?? '');
                $album = $album !== '' ? $album : (string)($row['album'] ?? '');
                $albumartist = $albumartist !== '' ? $albumartist : (string)($row['albumartist'] ?? '');
            }
        }

        $stmt->close();
    }
}

$flacfile = GEE_MUSIC_ROOT . '/' . ltrim($flacfileRel, '/');
$image = null;

if (is_file($flacfile)) {
    $getID3 = new getID3;
    $ThisFileInfo = $getID3->analyze($flacfile);

    if (isset($ThisFileInfo['comments']['picture'][0])) {
        $image = 'data:' .
            $ThisFileInfo['comments']['picture'][0]['image_mime'] .
            ';charset=utf-8;base64,' .
            base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
    }
}

$nexttitle = '';
$nextartist = '';

if (isset($mySimpleArray['pos']) && is_numeric($mySimpleArray['pos'])) {
    $nextPos = (int)$mySimpleArray['pos'] + 1;
    $queuearray = $mphpd->queue()->get($nextPos);

    if (is_array($queuearray)) {
        $nexttitle = (string)($queuearray['title'] ?? '');
        $nextartist = (string)($queuearray['artist'] ?? '');
    }
}

$rows = [
    'image' => $image,
    'title' => $title,
    'artist' => $artist,
    'album' => $album,
    'elapsed' => $elapsed,
    'duration' => $duration,
    'albumartist' => $albumartist,
    'volume' => $volume,
    'nexttitle' => $nexttitle,
    'nextartist' => $nextartist,
    'state' => $state,
    'renderer' => $geeRendererName,
    'renderer_display' => strtoupper((string)$geeRendererName),
    'active_stream' => $geeActiveStream,
    'allowed_streams' => $geeAllowedStreams,
    'stream_key' => $geeStreamKey,
    'stream_format' => $geeStreamFormat
];
echo json_encode($rows);