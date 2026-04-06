<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/streams.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

$conn = gee_db();

$rendererContext = gee_get_stream_context_from_renderer_globals();

$playlistFilename = gee_get_playlist_filename_from_stream($rendererContext);
$playlistName = gee_get_playlist_name_from_stream($rendererContext);
$playlistDirectory = gee_get_playlist_directory_from_stream($rendererContext);
$playlistPath = gee_get_playlist_path_from_stream($rendererContext);
$mpdHost = gee_get_mpd_host_from_stream($rendererContext);
$mpdPort = gee_get_mpd_port_from_stream($rendererContext);

if (!isset($sql) || trim((string)$sql) === '') {
    $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    throw new RuntimeException('Prepare failed: ' . $conn->error);
}

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    throw new RuntimeException('Execute failed: ' . $error);
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

if (!is_dir($playlistDirectory)) {
    throw new RuntimeException("Playlist directory does not exist: {$playlistDirectory}");
}

/*
|--------------------------------------------------------------------------
| Write .m3u file for visibility/debugging
|--------------------------------------------------------------------------
*/
$playlistText = implode("\n", $tracks) . "\n";

if (file_put_contents($playlistPath, $playlistText) === false) {
    throw new RuntimeException("Unable to write playlist file: {$playlistPath}");
}

/*
|--------------------------------------------------------------------------
| Connect to the correct MPD instance and build queue directly
|--------------------------------------------------------------------------
*/
$mphpd = new MphpD([
    'host' => $mpdHost,
    'port' => $mpdPort,
    'timeout' => 5,
]);

try {
    $mphpd->connect();

    // Clear queue
    $mphpd->queue()->clear();

    // Add every track directly into MPD queue
    foreach ($tracks as $track) {
        $mphpd->queue()->add($track);
    }

    // Start at first track, then pause so metadata is available
    $mphpd->player()->repeat(1);
    $mphpd->player()->play(0);
    $mphpd->player()->pause();

} catch (MPDException $e) {
    throw new RuntimeException('MPD error: ' . $e->getMessage(), 0, $e);
}

echo json_encode([
    'status' => 'ok',
    'renderer' => $rendererContext['display_name'] ?? ($rendererContext['hostname'] ?? null),
    'stream_key' => $rendererContext['stream_key'] ?? null,
    'stream_format' => $rendererContext['stream_format'] ?? null,
    'playlist_name' => $playlistName,
    'playlist_path' => $playlistPath,
    'track_count' => count($tracks),
    'mpd_host' => $mpdHost,
    'mpd_port' => $mpdPort
]);