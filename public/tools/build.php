<?php

declare(strict_types=1);

// =============================================================================
// Gee Library Build Script
//
// Recursively scans /mnt/music, extracts metadata from every supported audio
// file using getID3, and populates the app database table.
//
// Folder structure: any depth is supported
//   /mnt/music/Artist/Track.flac                     (flat)
//   /mnt/music/Artist/Album/Track.flac               (standard)
//   /mnt/music/Artist/Album/Disc 1/Track.flac        (multi-disc)
//   /mnt/music/Compilations/Album/Track.mp3          (compilations)
//
// MPD path: stored relative to /mnt/music so MPD can locate each file.
// =============================================================================

require_once '/var/www/app/core/bootstrap.php';
require_once '/var/www/app/api/getid3/getid3.php';

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

const MUSIC_ROOT  = '/mnt/music';
const BATCH_SIZE  = 100;   // Commit to DB every N tracks

// All extensions MPD 0.23 can decode (from mpd --version)
const AUDIO_EXTENSIONS = [
    'flac', 'mp3', 'mp2',
    'ogg', 'oga', 'opus',
    'wav', 'aiff', 'aif',
    'm4a', 'm4b', 'aac', 'mp4',
    'wv', 'mpc', 'ape',
    'dff', 'dsf',
    'wma', 'mka',
];

// ---------------------------------------------------------------------------
// Tag extraction
//
// getID3 stores tags under different source keys depending on format:
//   vorbiscomment → FLAC, OGG, OPUS
//   id3v2         → MP3, AIFF, WAV (if ID3-tagged)
//   id3v1         → MP3 fallback
//   quicktime     → M4A, AAC, MP4 (iTunes)
//   ape           → APE, WavPack
//   asf           → WMA
//
// Field names also vary by source — the map below covers all common variants.
// ---------------------------------------------------------------------------

const TAG_SOURCES = ['vorbiscomment', 'id3v2', 'id3v1', 'quicktime', 'ape', 'asf'];

const TAG_FIELDS = [
    'title'       => ['title'],
    'artist'      => ['artist'],
    'album'       => ['album'],
    'albumartist' => [
        'albumartist',   // vorbiscomment
        'album artist',  // vorbiscomment variant
        'album_artist',  // vorbiscomment variant
        'band',          // id3v2 TPE2
        'wm/albumartist', // asf
    ],
    'genre'  => ['genre'],
    'track'  => ['track', 'tracknumber', 'track_number', 'track number'],
];

function extract_tag(array $info, string $field): string
{
    $fieldVariants = TAG_FIELDS[$field] ?? [$field];

    foreach (TAG_SOURCES as $source) {
        $tags = $info['tags'][$source] ?? [];
        if (empty($tags)) {
            continue;
        }

        // Normalise tag keys to lowercase for case-insensitive matching
        $normTags = array_combine(
            array_map('strtolower', array_keys($tags)),
            array_values($tags)
        );

        foreach ($fieldVariants as $variant) {
            $value = $normTags[strtolower($variant)][0] ?? null;
            if ($value !== null && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }
    }

    return '';
}

// ---------------------------------------------------------------------------
// Track number normalisation
// Handles "3", "03", "3/12" → "3"
// ---------------------------------------------------------------------------
function normalise_track(string $raw): string
{
    if ($raw === '') {
        return '';
    }
    // Strip "total" part: "3/12" → "3"
    $raw = explode('/', $raw)[0];
    $raw = trim($raw);
    if (!ctype_digit($raw)) {
        return '';
    }
    // Remove leading zeros but keep "0" as "0"
    return (string)(int)$raw;
}

// ---------------------------------------------------------------------------
// Guess track number from filename if tag is missing
// "01 Money.flac" → "1", "Track 05.mp3" → "5"
// ---------------------------------------------------------------------------
function guess_track_from_filename(string $filename): string
{
    if (preg_match('/^(\d+)/', $filename, $m)) {
        return (string)(int)$m[1];
    }
    return '';
}

// ---------------------------------------------------------------------------
// Progress output
// ---------------------------------------------------------------------------
function out(string $msg): void
{
    echo $msg . "\n";
    flush();
}

// ---------------------------------------------------------------------------
// Artwork lookup helpers
// ---------------------------------------------------------------------------

/**
 * Check whether a file has embedded artwork using getID3.
 * Returns true if at least one picture is found in the file's tags.
 */
function has_embedded_artwork(getID3 $getID3, string $absolutePath): bool
{
    if (!is_file($absolutePath)) {
        return false;
    }

    $info = $getID3->analyze($absolutePath);
    return !empty($info['comments']['picture'][0]['data']);
}

/**
 * Try MusicBrainz Cover Art Archive, then iTunes Search as fallback.
 * Returns a URL string or null if nothing found.
 */
function gee_lookup_artwork(string $artist, string $album): ?string
{
    $url = gee_lookup_musicbrainz($artist, $album);
    if ($url !== null) {
        return $url;
    }
    return gee_lookup_itunes($artist, $album);
}

/**
 * Query MusicBrainz and return a Cover Art Archive URL if found.
 * Uses the search API to find the best release match, then checks CAA.
 */
function gee_lookup_musicbrainz(string $artist, string $album): ?string
{
    $query = sprintf(
        'release:"%s" AND artist:"%s"',
        str_replace('"', '', $album),
        str_replace('"', '', $artist)
    );

    $url = 'https://musicbrainz.org/ws/2/release?' . http_build_query([
        'query'  => $query,
        'fmt'    => 'json',
        'limit'  => '5',
    ]);

    $response = gee_http_get($url, [
        'User-Agent: GeePlayer/1.0 (https://genuineemotionengine.com)',
        'Accept: application/json',
    ]);

    if ($response === null) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['releases'])) {
        return null;
    }

    // Try each release in score order until we find one with cover art
    foreach ($data['releases'] as $release) {
        $mbid  = $release['id'] ?? '';
        $score = (int)($release['score'] ?? 0);

        if ($mbid === '' || $score < 70) {
            continue;
        }

        // Check if CAA has front art for this release
        // CAA returns 307 redirect for found art, 404 for missing
        $caaUrl = "https://coverartarchive.org/release/{$mbid}/front-500";
        if (gee_url_exists($caaUrl)) {
            return $caaUrl;
        }
    }

    return null;
}

/**
 * Query the iTunes Search API and return an artwork URL if found.
 * iTunes returns 100px art; we request 600px by rewriting the URL.
 */
function gee_lookup_itunes(string $artist, string $album): ?string
{
    $url = 'https://itunes.apple.com/search?' . http_build_query([
        'term'   => $artist . ' ' . $album,
        'entity' => 'album',
        'limit'  => '5',
    ]);

    $response = gee_http_get($url);

    if ($response === null) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['results'])) {
        return null;
    }

    foreach ($data['results'] as $result) {
        $artUrl = $result['artworkUrl100'] ?? '';
        if ($artUrl !== '') {
            // Upscale from 100px to 600px
            return str_replace('100x100bb', '600x600bb', $artUrl);
        }
    }

    return null;
}

/**
 * Simple HTTP GET with timeout. Returns response body or null on failure.
 */
function gee_http_get(string $url, array $headers = []): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_USERAGENT      => 'GeePlayer/1.0',
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code < 200 || $code >= 400) {
        return null;
    }

    return (string)$body;
}

/**
 * Check if a URL returns a success response (follows redirects).
 * Used to verify Cover Art Archive has art before storing the URL.
 */
function gee_url_exists(string $url): bool
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT      => 'GeePlayer/1.0',
    ]);

    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 400;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$conn = gee_db();

out('============================================================');
out('Gee Library Build');
out('============================================================');
out('Music root: ' . MUSIC_ROOT);
out('');

if (!is_dir(MUSIC_ROOT)) {
    out('ERROR: Music directory not found: ' . MUSIC_ROOT);
    exit(1);
}

// Truncate and start fresh
if (!$conn->query('TRUNCATE TABLE app')) {
    out('ERROR: Failed to truncate app table: ' . $conn->error);
    exit(1);
}
out('App table cleared.');
out('');

// Prepare insert statement
$stmt = $conn->prepare('
    INSERT INTO app (albumpath, artist, album, title, albumartist, idalbum, track, genre)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');

if (!$stmt) {
    out('ERROR: Failed to prepare insert: ' . $conn->error);
    exit(1);
}

// Configure getID3
$getID3 = new getID3();
$getID3->option_tag_id3v1  = true;
$getID3->option_tag_id3v2  = true;
$getID3->option_tag_apetag = true;
$getID3->option_tags_html  = false;  // Return raw UTF-8, not HTML-encoded
$getID3->option_md5_data   = false;  // Skip audio MD5 — not needed, saves time
$getID3->option_sha1_data  = false;
$getID3->option_extra_info = false;

// Build extension lookup set
$extSet = array_flip(AUDIO_EXTENSIONS);

// Recursive directory scan
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(MUSIC_ROOT, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$processed  = 0;
$inserted   = 0;
$fallbacks  = 0;
$errors     = 0;
$batchCount = 0;

$conn->begin_transaction();

foreach ($iterator as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    if (!$fileInfo->isFile()) {
        continue;
    }

    $ext = strtolower($fileInfo->getExtension());
    if (!isset($extSet[$ext])) {
        continue;
    }

    $processed++;
    $absolutePath = $fileInfo->getPathname();

    // MPD path = relative to music root (no leading slash)
    $mpdPath = ltrim(substr($absolutePath, strlen(MUSIC_ROOT)), DIRECTORY_SEPARATOR . '/');

    // ── Tag extraction ───────────────────────────────────────────────────
    $info = $getID3->analyze($absolutePath);

    $title       = extract_tag($info, 'title');
    $artist      = extract_tag($info, 'artist');
    $album       = extract_tag($info, 'album');
    $albumartist = extract_tag($info, 'albumartist');
    $genre       = extract_tag($info, 'genre');
    $trackRaw    = extract_tag($info, 'track');

    // ── Fallbacks for missing metadata ───────────────────────────────────
    $usedFallback = false;

    if ($title === '') {
        $title        = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
        $usedFallback = true;
    }

    // Decompose MPD path for folder-based fallbacks
    $pathParts = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $mpdPath));
    $depth     = count($pathParts);

    if ($artist === '' && $depth >= 1) {
        $artist       = $pathParts[0];
        $usedFallback = true;
    }

    if ($album === '') {
        if ($depth >= 3) {
            // Artist/Album/Track → second part is album
            $album = $pathParts[$depth - 2];
        } elseif ($depth === 2) {
            // Artist/Track → use artist folder as album
            $album = $pathParts[0];
        } else {
            $album = 'Unknown Album';
        }
        $usedFallback = true;
    }

    if ($albumartist === '') {
        $albumartist  = $artist;
        $usedFallback = true;
    }

    // ── Track number ─────────────────────────────────────────────────────
    $track = normalise_track($trackRaw);
    if ($track === '') {
        $track = guess_track_from_filename($fileInfo->getFilename());
    }

    // ── Album identifier (consistent regardless of folder structure) ─────
    $idalbum = md5(mb_strtolower($albumartist) . '|' . mb_strtolower($album));

    if ($usedFallback) {
        $fallbacks++;
    }

    // ── Insert ────────────────────────────────────────────────────────────
    $stmt->bind_param('ssssssss',
        $mpdPath,
        $artist,
        $album,
        $title,
        $albumartist,
        $idalbum,
        $track,
        $genre
    );

    if (!$stmt->execute()) {
        out("  ERROR inserting [{$mpdPath}]: " . $stmt->error);
        $errors++;
        continue;
    }

    $inserted++;
    $batchCount++;

    // ── Batch commit ──────────────────────────────────────────────────────
    if ($batchCount >= BATCH_SIZE) {
        $conn->commit();
        $conn->begin_transaction();
        $batchCount = 0;
        out("Inserting {$inserted} tracks... done");
    }
}

// Final commit
$conn->commit();
$stmt->close();

// ---------------------------------------------------------------------------
// Artwork lookup phase
// For each album, checks for embedded artwork first using getID3.
// External lookup (MusicBrainz → iTunes) only runs when no embedded art
// is found. Stores only a URL — no images saved on the server.
// Rate-limited to 1 request/second to respect MusicBrainz policy.
// ---------------------------------------------------------------------------
out('');
out('Checking for artwork...');

// Fetch unique albums with one sample track path each for embedded art check
$albumStmt = $conn->prepare('
    SELECT idalbum, albumartist, album,
           MIN(albumpath) AS sample_path
    FROM app
    WHERE idalbum != ""
    GROUP BY idalbum, albumartist, album
    ORDER BY albumartist ASC, album ASC
');

if ($albumStmt) {
    $albumStmt->execute();
    $albums = $albumStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $albumStmt->close();

    $artworkEmbedded = 0;
    $artworkFound    = 0;
    $artworkNotFound = 0;
    $artworkTotal    = count($albums);
    $externalLookups = 0;

    $updateStmt = $conn->prepare('
        UPDATE app SET artwork_url = ? WHERE idalbum = ?
    ');

    foreach ($albums as $i => $albumRow) {
        $albumartist = $albumRow['albumartist'];
        $album       = $albumRow['album'];
        $idalbum     = $albumRow['idalbum'];
        $samplePath  = MUSIC_ROOT . '/' . ltrim($albumRow['sample_path'], '/');

        // Check for embedded artwork first — no external lookup needed if found
        if (has_embedded_artwork($getID3, $samplePath)) {
            $artworkEmbedded++;
            continue;
        }

        // No embedded art — try MusicBrainz then iTunes
        $externalLookups++;
        $artworkUrl = gee_lookup_artwork($albumartist, $album);

        if ($artworkUrl !== null && $updateStmt) {
            $updateStmt->bind_param('ss', $artworkUrl, $idalbum);
            $updateStmt->execute();
            $artworkFound++;
        } else {
            $artworkNotFound++;
        }

        // Respect MusicBrainz rate limit — only sleep after external requests
        if ($externalLookups > 0 && $i < $artworkTotal - 1) {
            sleep(1);
        }
    }

    if ($updateStmt) {
        $updateStmt->close();
    }

    out('Artwork check done.');
} else {
    out('WARNING: Could not query albums for artwork lookup.');
    out('(Has the artwork_url column been added to the app table?');
    out(' Run: ALTER TABLE app ADD COLUMN artwork_url VARCHAR(500) DEFAULT NULL;)');
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
out('');
out('============================================================');
out('Build complete');
out('============================================================');
out("  Audio files scanned:   {$processed}");
out("  Tracks inserted:       {$inserted}");
out("  Used metadata fallback:{$fallbacks}");
out("  Errors:                {$errors}");
out('');

if ($inserted === 0) {
    out('WARNING: No tracks were inserted.');
    out('         Check that audio files exist under ' . MUSIC_ROOT);
    out('         and are in a supported format: ' . implode(', ', AUDIO_EXTENSIONS));
    out('');
    exit(1);
}

if ($fallbacks > 0) {
    out("NOTE: {$fallbacks} track(s) used folder/filename as fallback for missing tags.");
    out('      Consider tagging your files with a tool like MusicBrainz Picard,');
    out('      beets, or Mp3tag for best results.');
    out('');
}

if ($errors > 0) {
    out("WARNING: {$errors} track(s) failed to insert — check errors above.");
    out('');
    exit(1);
}

out('Library build successful.');
exit(0);
