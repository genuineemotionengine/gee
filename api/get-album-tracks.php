<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$conn = gee_db();

$album = trim($_GET['album'] ?? '');
$albumartist = trim($_GET['albumartist'] ?? '');

if ($album === '' || $albumartist === '') {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT id, track, title, artist, album, albumartist
    FROM app
    WHERE album = ?
      AND albumartist = ?
    ORDER BY
      CAST(NULLIF(track, '') AS UNSIGNED) ASC,
      title ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to prepare album track query'
    ]);
    exit;
}

$stmt->bind_param('ss', $album, $albumartist);
$stmt->execute();

$result = $stmt->get_result();
$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'id' => (int)$row['id'],
        'track' => $row['track'] ?? '',
        'title' => $row['title'] ?? '',
        'artist' => $row['artist'] ?? '',
        'album' => $row['album'] ?? '',
        'albumartist' => $row['albumartist'] ?? '',
    ];
}

$stmt->close();

echo json_encode($rows);
