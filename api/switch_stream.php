<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/renderer_runtime.php';
require_once __DIR__ . '/MphpD/MphpD.php';

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

header('Content-Type: application/json');

$targetStream = $_GET['stream'] ?? null;

if (!$targetStream) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing stream']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Resolve renderer
|--------------------------------------------------------------------------
*/
$rendererContext = gee_get_selected_renderer_context();

if ($rendererContext === null) {
    $rendererContext = gee_get_first_renderer_context();
}

if ($rendererContext === null) {
    http_response_code(500);
    echo json_encode(['error' => 'No renderer']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Validate stream belongs to renderer
|--------------------------------------------------------------------------
*/
$allowed = gee_get_allowed_streams_for_renderer($rendererContext);

if (!in_array($targetStream, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid stream for renderer']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Current runtime (source)
|--------------------------------------------------------------------------
*/
$currentRuntime = gee_get_renderer_runtime_context($rendererContext);

$currentHost = $currentRuntime['mpd_host'];
$currentPort = $currentRuntime['mpd_port'];

/*
|--------------------------------------------------------------------------
| Target runtime
|--------------------------------------------------------------------------
*/
$targetRuntime = gee_get_runtime_config_for_renderer_stream(
    $rendererContext,
    $targetStream
);

$targetHost = $targetRuntime['mpd_host'];
$targetPort = $targetRuntime['mpd_port'];

/*
|--------------------------------------------------------------------------
| Connect to both MPD instances
|--------------------------------------------------------------------------
*/
try {
    $sourceMpd = new MphpD([
        'host' => $currentHost,
        'port' => $currentPort,
    ]);
    $sourceMpd->connect();

    $targetMpd = new MphpD([
        'host' => $targetHost,
        'port' => $targetPort,
    ]);
    $targetMpd->connect();

} catch (MPDException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Capture current session
|--------------------------------------------------------------------------
*/
$status = $sourceMpd->status();
$currentSong = $sourceMpd->player()->current_song();
$queue = $sourceMpd->queue()->get();

$state = $status['state'] ?? 'stop';
$elapsed = (float)($status['elapsed'] ?? 0);
$currentPos = (int)($currentSong['pos'] ?? 0);

/*
|--------------------------------------------------------------------------
| Rebuild target queue
|--------------------------------------------------------------------------
*/
$targetMpd->queue()->clear();

foreach ($queue as $track) {
    if (!empty($track['file'])) {
        $targetMpd->queue()->add($track['file']);
    }
}

/*
|--------------------------------------------------------------------------
| Jump to same track + position
|--------------------------------------------------------------------------
*/
$targetMpd->player()->play($currentPos);
$targetMpd->player()->seekcur((string)$elapsed);

/*
|--------------------------------------------------------------------------
| Restore state
|--------------------------------------------------------------------------
*/
if ($state === 'pause') {
    $targetMpd->player()->pause();
}

/*
|--------------------------------------------------------------------------
| Save selected stream for renderer
|--------------------------------------------------------------------------
*/
gee_set_selected_stream_for_renderer($rendererContext, $targetStream);

echo json_encode([
    'status' => 'ok',
    'renderer' => $rendererContext['hostname'] ?? null,
    'new_stream' => $targetStream,
    'position' => $currentPos,
    'elapsed' => $elapsed
]);
