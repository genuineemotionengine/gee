<?php

declare(strict_types=1);

require_once '/var/www/app/core/bootstrap.php';
require_once '/var/www/app/core/streams.php';

$conn = gee_db();

$rendererContext = gee_get_stream_context_from_renderer_globals();

$playlistFilename = gee_get_playlist_filename_from_stream($rendererContext);
$playlistName = gee_get_playlist_name_from_stream($rendererContext);
$playlistDirectory = gee_get_playlist_directory_from_stream($rendererContext);
$playlistPath = gee_get_playlist_path_from_stream($rendererContext);
$mpdHost = gee_get_mpd_host_from_stream($rendererContext);
$mpdPort = gee_get_mpd_port_from_stream($rendererContext);

$mphpd = new MphpD([
    "host" => $mpdHost,
    "port" => $mpdPort,
    "timeout" => 5
]);

$myalbumarray = [];
$count = 0;

if (!isset($sql) || trim((string)$sql) === '') {
    $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit;
}

if (!$stmt->execute()) {
    echo "Execute failed: " . $stmt->error . "\n";
    $stmt->close();
    exit;
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $myalbumarray[$count++] = $row['albumpath'] . "\n";
}

$stmt->close();

if (empty($myalbumarray)) {
    echo "No tracks found for playlist generation.\n";
    exit;
}

if (!is_dir($playlistDirectory)) {
    echo "Playlist directory does not exist: {$playlistDirectory}\n";
    exit;
}

shuffle($myalbumarray);

$myfile = fopen($playlistPath, 'w');

if ($myfile === false) {
    echo "Unable to open playlist file for writing: {$playlistPath}\n";
    exit;
}

foreach ($myalbumarray as $line) {
    fwrite($myfile, $line);
}

fclose($myfile);

/*
|--------------------------------------------------------------------------
| Create MPD connection for the selected stream
|--------------------------------------------------------------------------
|
| Replace this section with your actual mphpd connection bootstrap if needed.
| The important thing is that the MPD client connects to:
|   $mpdHost
|   $mpdPort
|
*/
if (!class_exists('Mphpd')) {
    echo "Mphpd class is not available\n";
    exit;
}

$mphpd = new Mphpd([
    'host' => $mpdHost,
    'port' => $mpdPort,
]);

$mphpd->queue()->clear();
$mphpd->playlist($playlistName)->load([0]);
$mphpd->player()->repeat(MPD_STATE_ON);
$mphpd->player()->play(0);
$mphpd->player()->pause();

echo json_encode([
    'status' => 'ok',
    'renderer' => $rendererContext['display_name'] ?: ($rendererContext['hostname'] ?? null),
    'stream_key' => $rendererContext['stream_key'] ?? null,
    'stream_format' => $rendererContext['stream_format'] ?? null,
    'playlist_name' => $playlistName,
    'playlist_path' => $playlistPath,
    'mpd_host' => $mpdHost,
    'mpd_port' => $mpdPort
]);