<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/renderers.php';
require_once __DIR__ . '/../../core/runtime.php';

header('Content-Type: application/json; charset=utf-8');

function gee_playlist_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function gee_playlist_fail(string $message, int $statusCode = 500): void
{
    gee_playlist_json([
        'status' => 'error',
        'message' => $message,
    ], $statusCode);
}

function gee_playlist_mpd_command(array $runtime, string $command): array
{
    $host = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $port = (int)($runtime['mpd_port'] ?? 0);

    if ($port <= 0) {
        gee_playlist_fail('Invalid MPD port.');
    }

    $errno = 0;
    $errstr = '';

    $fp = @fsockopen($host, $port, $errno, $errstr, 3.0);

    if (!is_resource($fp)) {
        gee_playlist_fail('Failed to connect to MPD.');
    }

    stream_set_timeout($fp, 3, 0);

    fgets($fp);
    fwrite($fp, $command . "\n");

    $lines = [];

    while (!feof($fp)) {
        $line = trim((string)fgets($fp));

        if ($line === 'OK') {
            break;
        }

        if (str_starts_with($line, 'ACK')) {
            fclose($fp);
            gee_playlist_fail('MPD command failed.');
        }

        if ($line !== '') {
            $lines[] = $line;
        }
    }

    fclose($fp);

    return $lines;
}

function gee_playlist_parse_tracks(array $lines): array
{
    $tracks = [];
    $current = [];

    foreach ($lines as $line) {
        $parts = explode(': ', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $key = strtolower(trim($parts[0]));
        $value = trim($parts[1]);

        if ($key === 'file') {
            if (!empty($current)) {
                $tracks[] = $current;
            }

            $current = [
                'file' => $value,
            ];

            continue;
        }

        $current[$key] = $value;
    }

    if (!empty($current)) {
        $tracks[] = $current;
    }

    return $tracks;
}

function gee_playlist_key_values(array $lines): array
{
    $data = [];

    foreach ($lines as $line) {
        $parts = explode(': ', $line, 2);

        if (count($parts) !== 2) {
            continue;
        }

        $data[strtolower(trim($parts[0]))] = trim($parts[1]);
    }

    return $data;
}

$runtime = gee_get_active_runtime();

if (!is_array($runtime)) {
    gee_playlist_fail('No active renderer runtime available.');
}

$status = gee_playlist_key_values(gee_playlist_mpd_command($runtime, 'status'));
$tracks = gee_playlist_parse_tracks(gee_playlist_mpd_command($runtime, 'playlistinfo'));

$currentSong = isset($status['song']) ? (int)$status['song'] : -1;
$nextSong = $currentSong >= 0 ? $currentSong + 1 : -1;

$out = [];

foreach ($tracks as $track) {
    $position = isset($track['pos']) ? (int)$track['pos'] : -1;

    $out[] = [
        'id' => isset($track['id']) ? (int)$track['id'] : 0,
        'pos' => $position,
        'file' => (string)($track['file'] ?? ''),
        'title' => (string)($track['title'] ?? ''),
        'artist' => (string)($track['artist'] ?? ''),
        'album' => (string)($track['album'] ?? ''),
        'is_current' => $position === $currentSong,
        'is_next' => $position === $nextSong,
    ];
}

gee_playlist_json([
    'status' => 'ok',
    'current_song' => $currentSong,
    'next_song' => $nextSong,
    'track_count' => count($out),
    'tracks' => $out,
]);

