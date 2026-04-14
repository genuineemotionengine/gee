<?php

declare(strict_types=1);

const GEE_DB_HOST = '127.0.0.1';
const GEE_DB_NAME = 'gee';
const GEE_DB_USER = 'gee';
const GEE_DB_PASS = 'gee';

const GEE_MUSIC_ROOT = '/mnt/music';
const GEE_RENDERERS_DIR = '/var/lib/gee-core/renderers';

const GEE_SELECTED_RENDERER_COOKIE = 'gee_selected_renderer';
const GEE_SELECTED_STREAM_COOKIE = 'gee_selected_stream';

function gee_db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = @new mysqli(
        GEE_DB_HOST,
        GEE_DB_USER,
        GEE_DB_PASS,
        GEE_DB_NAME
    );

    if ($conn->connect_errno) {
        gee_fail('Database connection failed: ' . $conn->connect_error, 500);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function gee_json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function gee_fail(string $message, int $status = 400, array $extra = []): void
{
    gee_json_response(array_merge([
        'status' => 'error',
        'message' => $message,
    ], $extra), $status);
}