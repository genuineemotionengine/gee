<?php

declare(strict_types=1);

require_once '/var/www/app/core/bootstrap.php';
require_once '/var/www/app/core/streams.php';

$conn = gee_db();

$rendererContext = gee_get_stream_context_from_renderer_globals();

$streamKey = $rendererContext['stream_key'] ?? 'stream_safe';
$playlistFilename = gee_get_playlist_filename_from_stream($rendererContext);
$playlistPath = gee_get_playlist_path_from_stream($rendererContext);
$playlist = pathinfo($playlistFilename, PATHINFO_FILENAME);

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
| Load playlist into MPD using mphpd
|--------------------------------------------------------------------------
*/
if (!isset($mphpd)) {
    echo "mphpd is not available in loadplaylist.php\n";
    exit;
}

$mphpd->queue()->clear();

$mphpd->playlist($playlist)->load([0]);

$mphpd->player()->repeat(MPD_STATE_ON);

$mphpd->player()->play(0);

$mphpd->player()->pause();

echo json_encode([
    'status' => 'ok',
    'renderer' => $rendererContext['display_name'] ?: ($rendererContext['hostname'] ?? null),
    'stream_key' => $streamKey,
    'playlist_file' => $playlistFilename,
    'playlist_path' => $playlistPath
]);