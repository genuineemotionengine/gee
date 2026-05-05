<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$group = strtoupper(trim((string)($_GET['group'] ?? '0-9')));

$conn = gee_db();

if ($group === '0-9') {
    $sql = "
        SELECT id, track, title, artist, album
        FROM app
        WHERE title REGEXP '^[0-9]'
        ORDER BY title ASC, artist ASC, album ASC, CAST(track AS UNSIGNED), track, id
        LIMIT 500
    ";

    $stmt = $conn->prepare($sql);
} else {
    $sql = "
        SELECT id, track, title, artist, album
        FROM app
        WHERE UPPER(LEFT(title, 1)) = ?
        ORDER BY title ASC, artist ASC, album ASC, CAST(track AS UNSIGNED), track, id
        LIMIT 500
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $group);
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare track list query.'
    ]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$tracks = [];

while ($row = $result->fetch_assoc()) {
    $tracks[] = [
        'id' => (int)$row['id'],
        'track' => (string)($row['track'] ?? ''),
        'title' => (string)($row['title'] ?? ''),
        'artist' => (string)($row['artist'] ?? ''),
        'album' => (string)($row['album'] ?? ''),
    ];
}

$stmt->close();

echo json_encode([
    'status' => 'ok',
    'group' => $group,
    'track_count' => count($tracks),
    'tracks' => $tracks,
]);

