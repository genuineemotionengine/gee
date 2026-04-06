<?php

declare(strict_types=1);

require_once '/var/www/app/core/bootstrap.php';
require_once '/var/www/app/core/streams.php';
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

$tracks = [];

if (!isset($sql) || trim((string) $sql) === '') {
    $sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";
}

$result = $conn->query($sql);
if (!$result) {
    throw new RuntimeException('Playlist query failed: ' . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $albumpath = trim((string) ($row['albumpath'] ?? ''));
    if ($albumpath !== '') {
        $tracks[] = $albumpath;
    }
}

if (empty($tracks)) {
    throw new RuntimeException('No tracks found for playlist generation.');
}

if (!is_dir($playlistDirectory)) {
    throw new RuntimeException('Playlist directory does not exist: ' . $playlistDirectory);
}

shuffle($tracks);
$playlistBody = implode("\n", $tracks) . "\n";

if (file_put_contents($playlistPath, $playlistBody) === false) {
    throw new RuntimeException('Unable to write playlist file: ' . $playlistPath);
}

$mphpd = new MphpD([
    'host' => $mpdHost,
    'port' => $mpdPort,
    'timeout' => 5,
]);

try {
    $mphpd->connect();
    $mphpd->queue()->clear();
    $mphpd->playlist($playlistName)->load([0]);
    $mphpd->player()->repeat(1);
    $mphpd->player()->play(0);
    $mphpd->player()->pause();
} catch (MPDException $e) {
    throw new RuntimeException('Failed to load playlist into MPD: ' . $e->getMessage(), 0, $e);
}

if (!headers_sent()) {
    header('Content-Type: application/json');
}

echo json_encode([
    'status' => 'ok',
    'renderer' => gee_get_renderer_display_name($rendererContext),
    'stream_key' => $rendererContext['stream_key'] ?? null,
    'stream_format' => $rendererContext['stream_format'] ?? null,
    'playlist_name' => $playlistName,
    'playlist_path' => $playlistPath,
    'mpd_host' => $mpdHost,
    'mpd_port' => $mpdPort,
]);
