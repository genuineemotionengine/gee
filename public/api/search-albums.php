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
        MIN(id) AS id,
        album,
        albumartist,
        COUNT(*) AS track_count
    FROM app
    WHERE album LIKE CONCAT('%', ?, '%')
       OR albumartist LIKE CONCAT('%', ?, '%')
       OR artist LIKE CONCAT('%', ?, '%')
    GROUP BY albumartist, album
    ORDER BY albumartist ASC, album ASC
    LIMIT 50
");

$stmt->bind_param('sss', $q, $q, $q);
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