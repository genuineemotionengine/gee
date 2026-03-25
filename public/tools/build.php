<?php

declare(strict_types=1);

require_once '/var/www/app/core/bootstrap.php';
require_once '/var/www/app/api/getid3/getid3.php';

$conn = gee_db();

echo "1. Starting build\n";

/*
|--------------------------------------------------------------------------
| Reset app table
|--------------------------------------------------------------------------
*/
if (!$conn->query('TRUNCATE TABLE app')) {
    echo "Failed to truncate app table: " . $conn->error . "\n";
    exit(1);
}

echo "2. Cleared app table\n";

/*
|--------------------------------------------------------------------------
| Prepare insert statement once
|--------------------------------------------------------------------------
*/
$insertStmt = $conn->prepare("
    INSERT INTO app (
        albumpath,
        artist,
        album,
        title,
        albumartist,
        idalbum,
        track,
        genre
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insertStmt) {
    echo "Prepare failed for insert: " . $conn->error . "\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Scan music library
|--------------------------------------------------------------------------
*/
$dir = '/mnt/music/';

if (!is_dir($dir)) {
    echo "Music directory does not exist: {$dir}\n";
    exit(1);
}

$dirarray = scandir($dir);
if ($dirarray === false) {
    echo "Failed to scan music directory: {$dir}\n";
    exit(1);
}

$elements = count($dirarray);
$getID3 = new getID3();

$a = 1;

for ($x = 2; $x < $elements; $x++) {
    $artistFolder = $dirarray[$x];
    $subdir = $dir . $artistFolder . '/';

    if (!is_dir($subdir)) {
        continue;
    }

    $subdirarray = scandir($subdir);
    if ($subdirarray === false) {
        echo "Failed to scan subdirectory: {$subdir}\n";
        continue;
    }

    $subelements = count($subdirarray);

    for ($y = 2; $y < $subelements; $y++) {
        $fileName = $subdirarray[$y];
        $flacfile = $subdir . $fileName;

        if (!is_file($flacfile)) {
            continue;
        }

        $name = $artistFolder . '/' . $fileName;

        $ThisFileInfo = $getID3->analyze($flacfile);

        $track = '';
        $title = '';
        $artist = '';
        $album = '';
        $albumartist = '';
        $genre = '';

        if (isset($ThisFileInfo['tags']['id3v2']['track_number'][0])) {
            $track = (string)$ThisFileInfo['tags']['id3v2']['track_number'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['tracknumber'][0])) {
            $track = (string)$ThisFileInfo['tags']['vorbiscomment']['tracknumber'][0];
        }

        if (isset($ThisFileInfo['tags']['id3v2']['title'][0])) {
            $title = (string)$ThisFileInfo['tags']['id3v2']['title'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['title'][0])) {
            $title = (string)$ThisFileInfo['tags']['vorbiscomment']['title'][0];
        }

        if (isset($ThisFileInfo['tags']['id3v2']['artist'][0])) {
            $artist = (string)$ThisFileInfo['tags']['id3v2']['artist'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['artist'][0])) {
            $artist = (string)$ThisFileInfo['tags']['vorbiscomment']['artist'][0];
        }

        if (isset($ThisFileInfo['tags']['id3v2']['album'][0])) {
            $album = (string)$ThisFileInfo['tags']['id3v2']['album'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['album'][0])) {
            $album = (string)$ThisFileInfo['tags']['vorbiscomment']['album'][0];
        }

        if (isset($ThisFileInfo['tags']['id3v2']['band'][0])) {
            $albumartist = (string)$ThisFileInfo['tags']['id3v2']['band'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['albumartist'][0])) {
            $albumartist = (string)$ThisFileInfo['tags']['vorbiscomment']['albumartist'][0];
        }

        if (isset($ThisFileInfo['tags']['id3v2']['genre'][0])) {
            $genre = (string)$ThisFileInfo['tags']['id3v2']['genre'][0];
        } elseif (isset($ThisFileInfo['tags']['vorbiscomment']['genre'][0])) {
            $genre = (string)$ThisFileInfo['tags']['vorbiscomment']['genre'][0];
        }

        $title = str_replace("'", '&#39;', $title);
        $artist = str_replace("'", '&#39;', $artist);
        $album = str_replace("'", '&#39;', $album);
        $albumartist = str_replace("'", '&#39;', $albumartist);

        $idalbum = $artistFolder . $album;

        if (!$title || !$artist || !$album || !$albumartist) {
            echo "Skipping incomplete row:\n";
            echo "Path: {$name}\n";
            echo "Artist: {$artist}\n";
            echo "Album: {$album}\n";
            echo "Title: {$title}\n";
            echo "Album Artist: {$albumartist}\n";
            continue;
        }

        $insertStmt->bind_param(
            'ssssssss',
            $name,
            $artist,
            $album,
            $title,
            $albumartist,
            $idalbum,
            $track,
            $genre
        );

        if (!$insertStmt->execute()) {
            echo "Execute failed: " . $insertStmt->error . "\n";
            exit(1);
        }
    }

    $displayAlbumArtist = str_replace('&#39;', "'", $albumartist);
    $displayAlbum = str_replace('&#39;', "'", $album);

    echo $a . " - " . $displayAlbumArtist . " - " . $displayAlbum . " - " . $artistFolder . "\n";
    $a++;
}

$insertStmt->close();

echo "3. Database build complete\n";

/*
|--------------------------------------------------------------------------
| Build playlist file
|--------------------------------------------------------------------------
*/
$playlistStmt = $conn->prepare("
    SELECT albumpath
    FROM app
    WHERE genre != 'Relaxation'
");

if (!$playlistStmt) {
    echo "Prepare failed for playlist query: " . $conn->error . "\n";
    exit(1);
}

if (!$playlistStmt->execute()) {
    echo "Execute failed for playlist query: " . $playlistStmt->error . "\n";
    $playlistStmt->close();
    exit(1);
}

$result = $playlistStmt->get_result();
$myalbumarray = [];

while ($row = $result->fetch_assoc()) {
    $myalbumarray[] = $row['albumpath'] . "\n";
}

$playlistStmt->close();

if (empty($myalbumarray)) {
    echo "No tracks found for playlist generation.\n";
    exit(1);
}

shuffle($myalbumarray);

$playlistPath = '/var/lib/mpd/playlists/app.m3u';
$myfile = fopen($playlistPath, 'w');

if ($myfile === false) {
    echo "Unable to open playlist file for writing: {$playlistPath}\n";
    exit(1);
}

foreach ($myalbumarray as $line) {
    fwrite($myfile, $line);
}

fclose($myfile);

echo "4. Playlist written to {$playlistPath}\n";

/*
|--------------------------------------------------------------------------
| Load playlist into MPD and leave paused
|--------------------------------------------------------------------------
*/
echo "5. Loading playlist into MPD\n";

exec('sudo -u mpd mpc clear 2>&1', $outputClear, $rcClear);
exec('sudo -u mpd mpc load app 2>&1', $outputLoad, $rcLoad);
exec('sudo -u mpd mpc play 2>&1', $outputPlay, $rcPlay);
exec('sudo -u mpd mpc pause 2>&1', $outputPause, $rcPause);

if ($rcClear !== 0) {
    echo "mpc clear failed:\n" . implode("\n", $outputClear) . "\n";
    exit(1);
}

if ($rcLoad !== 0) {
    echo "mpc load app failed:\n" . implode("\n", $outputLoad) . "\n";
    exit(1);
}

if ($rcPlay !== 0) {
    echo "mpc play failed:\n" . implode("\n", $outputPlay) . "\n";
    exit(1);
}

if ($rcPause !== 0) {
    echo "mpc pause failed:\n" . implode("\n", $outputPause) . "\n";
    exit(1);
}

echo "6. MPD queue loaded and paused\n";
echo "7. Build finished successfully\n";