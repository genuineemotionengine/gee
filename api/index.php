<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/runtime.php';
require_once __DIR__ . '/getid3.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

function gee_build_and_load_playlist(array $runtime, string $whereSql): array
{
    $conn = gee_db();

    $stmt = $conn->prepare($whereSql);
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

    if (empty($tracks)) {
        throw new RuntimeException('No tracks found for playlist generation.');
    }

    shuffle($tracks);

    $playlistDirectory = (string)$runtime['playlist_directory'];
    $playlistPath = (string)$runtime['playlist_path'];

    if (!is_dir($playlistDirectory) && !mkdir($playlistDirectory, 0775, true) && !is_dir($playlistDirectory)) {
        throw new RuntimeException('Unable to create playlist directory: ' . $playlistDirectory);
    }

    $playlistText = implode("\n", $tracks) . "\n";

    if (file_put_contents($playlistPath, $playlistText) === false) {
        throw new RuntimeException('Unable to write playlist file: ' . $playlistPath);
    }

    $mphpd = new MphpD([
        'host' => (string)$runtime['mpd_host'],
        'port' => (int)$runtime['mpd_port'],
        'timeout' => 5,
    ]);

    $mphpd->connect();
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

parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);

$service = isset($qs['service']) ? (int)$qs['service'] : 0;
$mod = isset($qs['mod']) ? (int)$qs['mod'] : 0;

$rendererContext = gee_get_selected_or_first_renderer_context();
if ($rendererContext === null) {
    gee_fail('No registered renderer available.', 500);
}

$runtime = gee_get_renderer_runtime_context($rendererContext);
if ($runtime === null) {
    gee_fail('No renderer runtime context available.', 500);
}

try {
    $mphpd = new MphpD([
        'host' => (string)$runtime['mpd_host'],
        'port' => (int)$runtime['mpd_port'],
        'timeout' => 5,
    ]);

    $mphpd->connect();
} catch (MPDException $e) {
    gee_fail('MPD connection failed: ' . $e->getMessage(), 500);
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
        'renderer_id' => $rendererContext['renderer_id'],
        'renderer_display' => strtoupper((string)$rendererContext['display_name']),
        'active_stream' => $runtime['active_stream'],
        'stream_key' => $runtime['stream_key'],
        'stream_format' => $runtime['stream_format'],
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
    } catch (\Throwable $e) {
        gee_fail('Playlist load failed: ' . $e->getMessage(), 500);
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
    gee_json_response(['status' => 'ok', 'mod' => $mod]);
}

gee_fail('Unknown or unsupported service.', 400, ['service' => $service]);