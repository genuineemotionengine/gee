<?php

declare(strict_types=1);

define('GEE_APP_NAME', 'Gee');
define('GEE_TIMEZONE', 'Europe/London');
date_default_timezone_set(GEE_TIMEZONE);

define('GEE_BASE_PATH', dirname(__DIR__));
define('GEE_PUBLIC_PATH', GEE_BASE_PATH . '/public');
define('GEE_API_PATH', GEE_BASE_PATH . '/api');
define('GEE_CORE_PATH', GEE_BASE_PATH . '/core');

define('GEE_MUSIC_ROOT', '/mnt/music');
define('GEE_DEFAULT_ARTWORK', '/assets/images/default-album.png');

define('GEE_DB_HOST', 'localhost');
define('GEE_DB_PORT', 3306);
define('GEE_DB_NAME', 'gee');
define('GEE_DB_USER', 'gee');
define('GEE_DB_PASS', 'gee');

/*
|--------------------------------------------------------------------------
| Gee stream defaults
|--------------------------------------------------------------------------
|
| Keep these aligned with your systemd MPD instances.
|
*/
define('GEE_STREAM_SAFE_KEY', 'stream_safe');
define('GEE_STREAM_HIRES_KEY', 'stream_hires');

define('GEE_STREAM_SAFE_MPD_HOST', '127.0.0.1');
define('GEE_STREAM_SAFE_MPD_PORT', 6601);
define('GEE_STREAM_HIRES_MPD_HOST', '127.0.0.1');
define('GEE_STREAM_HIRES_MPD_PORT', 6602);

define('GEE_STREAM_SAFE_PLAYLIST_DIR', '/var/lib/mpd-safe/playlists');
define('GEE_STREAM_HIRES_PLAYLIST_DIR', '/var/lib/mpd-hires/playlists');

define('GEE_STREAM_SAFE_PLAYLIST_FILE', 'stream_safe.m3u');
define('GEE_STREAM_HIRES_PLAYLIST_FILE', 'stream_hires.m3u');
