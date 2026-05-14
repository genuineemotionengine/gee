<?php

declare(strict_types=1);

// =============================================================================
// Gee Music Pre-flight Check
//
// This script replaced the original file-renaming approach (music.php v1).
//
// The original renamed every file to a sequential number (100000.flac etc.)
// to work around an MPD filename issue.  MPD 0.23+ handles Unicode filenames
// natively — no renaming is needed or wanted.
//
// This script now:
//   1. Verifies /mnt/music is present and mounted
//   2. Counts supported audio files by format
//   3. Reports findings to the installer log
//   4. Exits 0 on success so task 09 can proceed to build.php
// =============================================================================

const MUSIC_ROOT = '/mnt/music';

const SUPPORTED_EXTENSIONS = [
    // Lossless
    'flac', 'wav', 'aiff', 'aif', 'wv', 'ape', 'dff', 'dsf',
    // Lossy
    'mp3', 'mp2', 'ogg', 'oga', 'opus', 'aac', 'm4a', 'm4b',
    'mp4', 'mpc', 'wma', 'mka',
];

// ---------------------------------------------------------------------------
// Count audio files recursively, grouped by extension
// ---------------------------------------------------------------------------
function count_audio_files(string $dir): array
{
    $extSet  = array_flip(SUPPORTED_EXTENSIONS);
    $counts  = [];
    $total   = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!isset($extSet[$ext])) {
            continue;
        }
        $counts[$ext] = ($counts[$ext] ?? 0) + 1;
        $total++;
    }

    arsort($counts);
    return ['total' => $total, 'by_format' => $counts];
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------
echo "Gee Music Pre-flight Check\n";
echo "Music root: " . MUSIC_ROOT . "\n";
echo "\n";

if (!is_dir(MUSIC_ROOT)) {
    echo "ERROR: Music directory not found: " . MUSIC_ROOT . "\n";
    echo "       Ensure the music SSD is mounted before running the installer.\n";
    exit(1);
}

// Check the directory isn't just an empty mount point
$entries = array_diff((array)scandir(MUSIC_ROOT), ['.', '..']);

if (empty($entries)) {
    echo "WARNING: Music directory is empty or not yet populated: " . MUSIC_ROOT . "\n";
    echo "         build.php will report 0 tracks. Add music and run the\n";
    echo "         build script manually when ready:\n";
    echo "           php /var/www/app/public/tools/build.php\n";
    echo "\n";
    echo "Pre-flight check passed (empty library).\n";
    exit(0);
}

$result = count_audio_files(MUSIC_ROOT);
$total  = $result['total'];
$byFmt  = $result['by_format'];

if ($total === 0) {
    echo "WARNING: No supported audio files found in " . MUSIC_ROOT . "\n";
    echo "         Supported formats: " . implode(', ', SUPPORTED_EXTENSIONS) . "\n";
    echo "\n";
    echo "Pre-flight check passed (no audio files found).\n";
    exit(0);
}

echo "Found {$total} supported audio file(s):\n";
foreach ($byFmt as $ext => $n) {
    $bar = str_repeat('█', (int)min(40, round($n / max($total, 1) * 40)));
    printf("  .%-6s %5d  %s\n", $ext, $n, $bar);
}

echo "\nSupported formats: " . implode(', ', SUPPORTED_EXTENSIONS) . "\n";
echo "\nPre-flight check passed.\n";
exit(0);
