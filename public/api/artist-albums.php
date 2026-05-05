<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$artist = trim((string)($_GET['artist'] ?? ''));

if ($artist === '') {
    echo json_encode([]);
    exit;
}

$stmt = gee_db()->prepare("
    SELECT
        MIN(id) AS id,
        album,
        albumartist,
        COUNT(*) AS track_count
    FROM app
    WHERE artist = ?
       OR albumartist = ?
    GROUP BY albumartist, album
    ORDER BY albumartist ASC, album ASC
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare artist albums.'
    ]);
    exit;
}

$stmt->bind_param('ss', $artist, $artist);
$stmt->execute();

$result = $stmt->get_result();
$albums = [];

while ($row = $result->fetch_assoc()) {
    $albums[] = [
        'id' => (int)$row['id'],
        'album' => (string)($row['album'] ?? ''),
        'albumartist' => (string)($row['albumartist'] ?? ''),
        'track_count' => (int)($row['track_count'] ?? 0),
    ];
}

$stmt->close();

echo json_encode($albums);