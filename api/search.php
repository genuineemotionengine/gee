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
    SELECT id, track, title, artist, album
    FROM app
    WHERE title LIKE CONCAT('%', ?, '%')
       OR artist LIKE CONCAT('%', ?, '%')
       OR album LIKE CONCAT('%', ?, '%')
    ORDER BY artist ASC, album ASC, track ASC
    LIMIT 50
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to prepare search query'
    ]);
    exit;
}

$stmt->bind_param('sss', $q, $q, $q);
$stmt->execute();

$result = $stmt->get_result();
$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'id' => (int)$row['id'],
        'track' => $row['track'],
        'title' => $row['title'],
        'artist' => $row['artist'],
        'album' => $row['album'],
    ];
}

$stmt->close();

echo json_encode($rows);
