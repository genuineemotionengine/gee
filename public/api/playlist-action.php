<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';
require_once __DIR__ . '/../../core/renderers.php';
require_once __DIR__ . '/../../core/runtime.php';

header('Content-Type: application/json; charset=utf-8');

function gee_playlist_action_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function gee_playlist_action_fail(string $message, int $statusCode = 400): void
{
    gee_playlist_action_json([
        'status' => 'error',
        'message' => $message,
    ], $statusCode);
}

function gee_playlist_action_mpd_command(array $runtime, string $command): void
{
    $host = (string)($runtime['mpd_host'] ?? '127.0.0.1');
    $port = (int)($runtime['mpd_port'] ?? 0);

    if ($port <= 0) {
        gee_playlist_action_fail('Invalid MPD port.', 500);
    }

    $fp = @fsockopen($host, $port, $errno, $errstr, 3.0);

    if (!is_resource($fp)) {
        gee_playlist_action_fail('Failed to connect to MPD.', 500);
    }

    stream_set_timeout($fp, 3, 0);

    fgets($fp);
    fwrite($fp, $command . "\n");

    while (!feof($fp)) {
        $line = trim((string)fgets($fp));

        if ($line === 'OK') {
            fclose($fp);
            return;
        }

        if (str_starts_with($line, 'ACK')) {
            fclose($fp);
            gee_playlist_action_fail('MPD command failed: ' . $line, 500);
        }
    }

    fclose($fp);
}

$payload = json_decode((string)file_get_contents('php://input'), true);

if (!is_array($payload)) {
    gee_playlist_action_fail('Invalid JSON payload.');
}

$action = (string)($payload['action'] ?? '');
$mpdId = (int)($payload['mpd_id'] ?? 0);

if ($action !== 'play_now') {
    gee_playlist_action_fail('Unsupported playlist action.');
}

if ($mpdId <= 0) {
    gee_playlist_action_fail('Missing MPD playlist id.');
}

$runtime = gee_get_active_runtime();

if (!is_array($runtime)) {
    gee_playlist_action_fail('No active renderer runtime available.', 500);
}

gee_playlist_action_mpd_command($runtime, 'playid ' . $mpdId);

gee_playlist_action_json([
    'status' => 'ok',
    'action' => $action,
    'mpd_id' => $mpdId,
]);

