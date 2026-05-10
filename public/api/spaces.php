<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function gee_spaces_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function gee_spaces_run(array $args): void
{
    $script = '/usr/local/bin/gee-spaces.sh';

    if (!is_file($script) || !is_executable($script)) {
        gee_spaces_response([
            'success' => false,
            'message' => 'Gee spaces script is missing or not executable',
        ], 500);
    }

    $command = '/usr/bin/sudo ' . escapeshellarg($script);

    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg((string)$arg);
    }

    $output = [];
    $exitCode = 0;

    exec($command . ' 2>&1', $output, $exitCode);

    $raw = implode("\n", $output);
    $json = json_decode($raw, true);

    if ($exitCode !== 0) {
        gee_spaces_response([
            'success' => false,
            'message' => 'Gee spaces command failed',
            'exit_code' => $exitCode,
            'output' => $output,
        ], 500);
    }

    if (!is_array($json)) {
        gee_spaces_response([
            'success' => false,
            'message' => 'Gee spaces command did not return valid JSON',
            'output' => $output,
        ], 500);
    }

    gee_spaces_response($json);
}

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? 'list'));

switch ($action) {
    case 'list':
        gee_spaces_run(['list']);
        break;

    case 'select-renderer':
        $rendererId = trim((string)($_POST['renderer_id'] ?? $_GET['renderer_id'] ?? ''));
        $stream = trim((string)($_POST['stream'] ?? $_GET['stream'] ?? 'safe'));
        gee_spaces_run(['select-renderer', $rendererId, $stream]);
        break;

    case 'create-room':
        $roomName = trim((string)($_POST['room_name'] ?? $_GET['room_name'] ?? ''));
        gee_spaces_run(['create-room', $roomName]);
        break;

    case 'add-renderer':
        $roomId = trim((string)($_POST['room_id'] ?? $_GET['room_id'] ?? ''));
        $rendererId = trim((string)($_POST['renderer_id'] ?? $_GET['renderer_id'] ?? ''));
        gee_spaces_run(['add-renderer', $roomId, $rendererId]);
        break;

    case 'remove-renderer':
        $roomId = trim((string)($_POST['room_id'] ?? $_GET['room_id'] ?? ''));
        $rendererId = trim((string)($_POST['renderer_id'] ?? $_GET['renderer_id'] ?? ''));
        gee_spaces_run(['remove-renderer', $roomId, $rendererId]);
        break;

    case 'select-room':
        $roomId = trim((string)($_POST['room_id'] ?? $_GET['room_id'] ?? ''));
        $stream = trim((string)($_POST['stream'] ?? $_GET['stream'] ?? 'safe'));
        gee_spaces_run(['select-room', $roomId, $stream]);
        break;

    default:
        gee_spaces_response([
            'success' => false,
            'message' => 'Unknown spaces action',
            'action' => $action,
        ], 400);
}
