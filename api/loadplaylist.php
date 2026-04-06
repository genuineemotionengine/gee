<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/streams.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

$conn = gee_db();

$rendererContext = gee_get_stream_context_from_renderer_globals();

$playlistName = gee_get_playlist_name_from_stream($rendererContext);
$playlistDirectory = gee_get_playlist_directory_from_stream($rendererContext);
$playlistPath = gee_get_playlist_path_from_stream($rendererContext);
$mpdHost = gee_get_mpd_host_from_stream($rendererContext);
$mpdPort = gee_get_mpd_port_from_stream($rendererContext);

$myalbumarray = [];

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

while ($row = $result->fetch_assoc()) {
    $myalbumarray[] = $row['albumpath'] . "\n";
}

$stmt->close();

if (empty($myalbumarray)) {
    throw new RuntimeException('No tracks found for playlist generation.');
}

if (!is_dir($playlistDirectory)) {
    throw new RuntimeException("Playlist directory does not exist: {$playlistDirectory}");
}

shuffle($myalbumarray);

$myfile = fopen($playlistPath, 'w');

if ($myfile === false) {
    throw new RuntimeException("Unable to open playlist file for writing: {$playlistPath}");
}

foreach ($myalbumarray as $line) {
    fwrite($myfile, $line);
}

fclose($myfile);

$mphpd = new MphpD([
    'host' => $mpdHost,
    'port' => $mpdPort,
    'timeout' => 5,
]);

try {
    $mphpd->connect();
    $mphpd->queue()->clear();

    // Use MPD command interface rather than saved-playlist API assumptions
    $mphpd->sendCommand('load', [$playlistName]);
    $mphpd->player()->repeat(1);
    $mphpd->player()->play(0);
    $mphpd->player()->pause();
} catch (MPDException $e) {
    throw new RuntimeException('MPD error: ' . $e->getMessage(), 0, $e);
}