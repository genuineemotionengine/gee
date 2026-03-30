<?php

require_once '/var/www/app/core/bootstrap.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$conn = gee_db();

$stmt = $conn->prepare("
    INSERT INTO renderers (
        hostname,
        ip,
        platform,
        audio_profile,
        alsa_device,
        max_sample_rate,
        bit_depth,
        channels,
        quality_tier,
        preferred_stream_format
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        ip = VALUES(ip),
        audio_profile = VALUES(audio_profile),
        alsa_device = VALUES(alsa_device),
        max_sample_rate = VALUES(max_sample_rate),
        bit_depth = VALUES(bit_depth),
        channels = VALUES(channels),
        quality_tier = VALUES(quality_tier),
        preferred_stream_format = VALUES(preferred_stream_format),
        updated_at = CURRENT_TIMESTAMP
");

$stmt->bind_param(
    "sssssiisss",
    $input['hostname'],
    $input['ip'],
    $input['platform'],
    $input['audio_profile'],
    $input['alsa_device'],
    $input['max_sample_rate'],
    $input['bit_depth'],
    $input['channels'],
    $input['quality_tier'],
    $input['preferred_stream_format']
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
    exit;
}

echo json_encode(['status' => 'ok']);

