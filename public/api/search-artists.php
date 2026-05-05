<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = gee_db()->prepare("
    SELECT
        artist,
        COUNT(*) AS track_count,
        COUNT(DISTINCT album) AS album_count
    FROM app
    WHERE artist LIKE CONCAT('%', ?, '%')
       OR albumartist LIKE CONCAT('%', ?, '%')
    GROUP BY artist
    ORDER BY artist ASC
    LIMIT 50
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare artist search.'
    ]);
    exit;
}

$stmt->bind_param('ss', $q, $q);
$stmt->execute();

$result = $stmt->get_result();
$artists = [];

while ($row = $result->fetch_assoc()) {
    $artist = (string)($row['artist'] ?? '');

    if ($artist === '') {
        continue;
    }

    $artists[] = [
        'artist' => $artist,
        'track_count' => (int)($row['track_count'] ?? 0),
        'album_count' => (int)($row['album_count'] ?? 0),
    ];
}

$stmt->close();

echo json_encode($artists);