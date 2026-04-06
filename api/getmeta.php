<?php

declare(strict_types=1);

require_once '/var/www/app/core/streams.php';

$geeRendererContext = gee_get_stream_context_from_renderer_globals();
$geeRendererName = gee_get_renderer_display_name($geeRendererContext);
$geeStreamKey = $geeRendererContext['stream_key'] ?? null;
$geeStreamFormat = $geeRendererContext['stream_format'] ?? null;
$geeFifoPath = $geeRendererContext['fifo_path'] ?? null;
$geeMpdHost = gee_get_mpd_host_from_stream($geeRendererContext);
$geeMpdPort = gee_get_mpd_port_from_stream($geeRendererContext);

$image = GEE_DEFAULT_ARTWORK;
$nexttitle = null;
$nextartist = null;

$statusarray = $mphpd->status();

if (!empty($verbose)) {
    echo "Status";
    echo '<pre>' . htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE) . '</pre>';
    echo "<br><br><br>";
}

$elapsedRaw = (string) ($statusarray['elapsed'] ?? '0');
$durationRaw = (string) ($statusarray['duration'] ?? '0');
$volume = $statusarray['volume'] ?? null;
$state = $statusarray['state'] ?? 'stop';

$elapsedParts = explode('.', $elapsedRaw);
$durationParts = explode('.', $durationRaw);

$elapsed = $elapsedParts[0] ?? '0';
$duration = $durationParts[0] ?? '0';

$mySimpleArray = $mphpd->player()->current_song();

if (!empty($verbose)) {
    echo "Current Song";
    echo '<pre>' . htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE) . '</pre>';
    echo "<br><br><br>";
}

$relativeFile = (string) ($mySimpleArray['file'] ?? '');
$album = (string) ($mySimpleArray['album'] ?? '');
$artist = (string) ($mySimpleArray['artist'] ?? '');
$title = (string) ($mySimpleArray['title'] ?? '');
$albumartist = (string) ($mySimpleArray['albumartist'] ?? '');

if (stripos($albumartist, 'Various Artists - ') === 0) {
    $albumartist = 'Various Artists';
}

if ($relativeFile !== '') {
    $flacfile = rtrim(GEE_MUSIC_ROOT, '/') . '/' . ltrim($relativeFile, '/');

    if (is_file($flacfile)) {
        $getID3 = new getID3();
        $ThisFileInfo = $getID3->analyze($flacfile);

        if (isset($ThisFileInfo['comments']['picture'][0])) {
            $image = 'data:'
                . $ThisFileInfo['comments']['picture'][0]['image_mime']
                . ';charset=utf-8;base64,'
                . base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
        }
    }
}

$currentPos = isset($mySimpleArray['pos']) ? (int) $mySimpleArray['pos'] : null;

if ($currentPos !== null) {
    $queuearray = $mphpd->queue()->get($currentPos + 1);
    if (is_array($queuearray)) {
        $nexttitle = $queuearray['title'] ?? null;
        $nextartist = $queuearray['artist'] ?? null;
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
    'stream_format' => $geeStreamFormat,
    'fifo_path' => $geeFifoPath,
    'mpd_host' => $geeMpdHost,
    'mpd_port' => $geeMpdPort,
];

header('Content-Type: application/json');
echo json_encode($rows);
