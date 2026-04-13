<?php

declare(strict_types=1);

const GEE_DB_HOST = '127.0.0.1';
const GEE_DB_NAME = 'gee';
const GEE_DB_USER = 'gee';
const GEE_DB_PASS = 'gee';
const GEE_MUSIC_ROOT = '/mnt/music';
const GEE_RENDERERS_DIR = '/var/lib/gee-core/renderers';
const GEE_SELECTED_RENDERER_COOKIE = 'gee_selected_renderer';

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