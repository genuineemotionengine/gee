<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/streams.php';

$geeRendererContext = gee_get_stream_context_from_renderer_globals();

$geeRendererName = null;
$geeStreamKey = null;
$geeStreamFormat = null;
$geeFifoPath = null;
$geeMpdHost = '127.0.0.1';
$geeMpdPort = 6601;

if (is_array($geeRendererContext)) {
    $geeRendererName = $geeRendererContext['display_name'] ?? ($geeRendererContext['hostname'] ?? null);
    $geeStreamKey = $geeRendererContext['stream_key'] ?? null;
    $geeStreamFormat = $geeRendererContext['stream_format'] ?? null;
    $geeFifoPath = $geeRendererContext['fifo_path'] ?? null;
    $geeMpdHost = gee_get_mpd_host_from_stream($geeRendererContext);
    $geeMpdPort = gee_get_mpd_port_from_stream($geeRendererContext);
}

$statusarray = $mphpd->status();

if (!is_array($statusarray)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'MPD status() did not return an array.',
        'renderer' => $geeRendererName,
        'stream_key' => $geeStreamKey,
        'stream_format' => $geeStreamFormat,
    ]);
    exit;
}

if ($verbose ?? false) {
    echo "Status";
    echo '<pre>' . htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE) . '</pre>';
    echo "<br><br><br>";
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

if ($verbose ?? false) {
    echo "Current Song";
    echo '<pre>' . htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE) . '</pre>';
    echo "<br><br><br>";
}

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
    'stream_key' => $geeStreamKey,
    'stream_format' => $geeStreamFormat
];

echo json_encode($rows);