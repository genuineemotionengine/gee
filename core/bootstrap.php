<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Load DB credentials from /etc/gee/app.conf (written by the Gee installer).
// Never hardcode credentials here — this file is in version control.
//
// /etc/gee/app.conf format (shell key=value, written by installer task 09):
//   GEE_DB_NAME="gee"
//   GEE_DB_USER="gee"
//   GEE_DB_PASS="<random password generated at install time>"
// ---------------------------------------------------------------------------
function gee_load_app_conf(): array
{
    $confFile = '/etc/gee/app.conf';

    if (!is_file($confFile) || !is_readable($confFile)) {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Service configuration unavailable.']);
        exit;
    }

    $values = [];
    foreach (file($confFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $values[trim($parts[0])] = trim($parts[1], '"\'');
        }
    }

    return $values;
}

$_geeConf = gee_load_app_conf();

define('GEE_DB_HOST', '127.0.0.1');
define('GEE_DB_NAME', $_geeConf['GEE_DB_NAME'] ?? 'gee');
define('GEE_DB_USER', $_geeConf['GEE_DB_USER'] ?? 'gee');
define('GEE_DB_PASS', $_geeConf['GEE_DB_PASS'] ?? '');
unset($_geeConf);

const GEE_MUSIC_ROOT               = '/mnt/music';
const GEE_RENDERERS_DIR            = '/var/lib/gee-core/renderers';
const GEE_SELECTED_RENDERER_COOKIE = 'gee_selected_renderer';
const GEE_SELECTED_STREAM_COOKIE   = 'gee_selected_stream';

function gee_db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(
        GEE_DB_HOST,
        GEE_DB_USER,
        GEE_DB_PASS,
        GEE_DB_NAME
    );

    $conn->set_charset('utf8mb4');

    return $conn;
}

function gee_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function gee_fail(string $message, int $status = 400, array $extra = []): never
{
    gee_json_response(array_merge([
        'status' => 'error',
        'message' => $message,
    ], $extra), $status);
}

function gee_safe_renderer_id(string $value): string
{
    return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
}

function gee_start_session_if_needed(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}