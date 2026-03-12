<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$conn = gee_db();
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT
        MIN(id) AS id,
        album,
        albumartist,
        COUNT(*) AS track_count,
        MIN(albumpath) AS sample_path
    FROM app
    WHERE album LIKE CONCAT('%', ?, '%')
       OR albumartist LIKE CONCAT('%', ?, '%')
    GROUP BY albumartist, album
    ORDER BY albumartist ASC, album ASC
    LIMIT 50
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to prepare album search query'
    ]);
    exit;
}

$stmt->bind_param('ss', $q, $q);
$stmt->execute();

$result = $stmt->get_result();
$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'id' => (int)$row['id'],
        'album' => $row['album'] ?? '',
        'albumartist' => $row['albumartist'] ?? '',
        'track_count' => (int)$row['track_count'],
        'sample_path' => $row['sample_path'] ?? '',
    ];
}

$stmt->close();

echo json_encode($rows);

