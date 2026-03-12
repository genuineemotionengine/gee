<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function gee_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    $db = new mysqli(
        GEE_DB_HOST,
        GEE_DB_USER,
        GEE_DB_PASS,
        GEE_DB_NAME,
        GEE_DB_PORT
    );

    if ($db->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $db->connect_error);
    }

    $db->set_charset('utf8mb4');

    return $db;
}

