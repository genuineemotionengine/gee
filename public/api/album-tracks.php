<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$album = trim((string)($_GET['album'] ?? ''));
$albumartist = trim((string)($_GET['albumartist'] ?? ''));

if ($album === '') {
    echo json_encode([]);
    exit;
}

if ($albumartist !== '') {
    $stmt = gee_db()->prepare("
        SELECT id, track, title, artist, album, albumartist
        FROM app
        WHERE album = ?
          AND albumartist = ?
        ORDER BY CAST(track AS UNSIGNED), track, id
    ");

    $stmt->bind_param('ss', $album, $albumartist);
} else {
    $stmt = gee_db()->prepare("
        SELECT id, track, title, artist, album, albumartist
        FROM app
        WHERE album = ?
        ORDER BY CAST(track AS UNSIGNED), track, id
    ");

    $stmt->bind_param('s', $album);
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
        'albumartist' => (string)($row['albumartist'] ?? ''),
    ];
}

$stmt->close();

echo json_encode($tracks);

