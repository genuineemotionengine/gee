<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/runtime.php';

$message = '';
$messageType = 'info';

$selectRendererId = gee_safe_renderer_id((string)($_GET['select'] ?? ''));
$selectStream = trim((string)($_GET['stream'] ?? ''));

if ($selectRendererId !== '') {
    $rendererContext = gee_get_renderer_context($selectRendererId);

    if (!is_array($rendererContext)) {
        $message = 'Renderer not found: ' . htmlspecialchars($selectRendererId, ENT_QUOTES, 'UTF-8');
        $messageType = 'error';
    } else {
        $rendererOk = gee_set_selected_renderer_cookie($selectRendererId);
        $streamOk = true;

        if ($selectStream !== '') {
            if (!gee_is_valid_stream_key($selectStream)) {
                $message = 'Invalid stream selection.';
                $messageType = 'error';
                $streamOk = false;
            } else {
                $streamOk = gee_set_selected_stream_cookie($selectStream);
            }
        }

        if ($rendererOk && $streamOk) {
            $url = '/renderers.php?selected=' . rawurlencode($selectRendererId);
            if ($selectStream !== '' && gee_is_valid_stream_key($selectStream)) {
                $url .= '&stream_selected=' . rawurlencode($selectStream);
            }
            header('Location: ' . $url);
            exit;
        }

        if ($message === '') {
            $message = 'Failed to update selection.';
            $messageType = 'error';
        }
    }
}

if ($selectRendererId === '' && $selectStream !== '') {
    if (!gee_is_valid_stream_key($selectStream)) {
        $message = 'Invalid stream selection.';
        $messageType = 'error';
    } else {
        if (gee_set_selected_stream_cookie($selectStream)) {
            header('Location: /renderers.php?stream_selected=' . rawurlencode($selectStream));
            exit;
        }

        $message = 'Failed to set selected stream cookie.';
        $messageType = 'error';
    }
}

if (isset($_GET['selected'])) {
    $selectedNotice = gee_safe_renderer_id((string)$_GET['selected']);
    if ($selectedNotice !== '') {
        $message = 'Selected renderer: ' . htmlspecialchars($selectedNotice, ENT_QUOTES, 'UTF-8');
        $messageType = 'success';
    }
}

if (isset($_GET['stream_selected'])) {
    $streamNotice = trim((string)$_GET['stream_selected']);
    if (gee_is_valid_stream_key($streamNotice)) {
        $message = 'Selected stream: ' . htmlspecialchars($streamNotice, ENT_QUOTES, 'UTF-8');
        $messageType = 'success';
    }
}

$selectedRendererId = gee_get_selected_renderer_id();
$selectedStream = gee_get_selected_stream();
$renderers = gee_get_all_renderer_contexts();

$selectedRendererLabel = '';

foreach ($renderers as $rendererContext) {
    $rendererId = (string)($rendererContext['renderer_id'] ?? '');

    if ($selectedRendererId !== null && $rendererId === $selectedRendererId) {
        $rendererName = (string)($rendererContext['renderer_name'] ?? '');
        $displayName = (string)($rendererContext['display_name'] ?? '');
        $hostname = (string)($rendererContext['hostname'] ?? '');

        $selectedRendererLabel = $rendererName !== ''
            ? $rendererName
            : ($displayName !== ''
                ? $displayName
                : ($hostname !== '' ? $hostname : $rendererId));

        break;
    }
}

$rows = [];

foreach ($renderers as $rendererContext) {
    $runtime = gee_get_renderer_runtime_context($rendererContext);
    $rendererId = (string)($rendererContext['renderer_id'] ?? '');

    $safeRuntime = is_array($runtime) ? gee_get_renderer_stream_runtime($runtime, 'safe') : null;
    $hiresRuntime = is_array($runtime) ? gee_get_renderer_stream_runtime($runtime, 'hires') : null;

    $safePlaylistExists = is_array($safeRuntime) && is_file((string)($safeRuntime['playlist_path'] ?? ''));
    $hiresPlaylistExists = is_array($hiresRuntime) && is_file((string)($hiresRuntime['playlist_path'] ?? ''));
    $configVersion = is_array($runtime) ? (string)($runtime['config_version'] ?? '') : '';

    $rows[] = [
        'renderer' => $rendererContext,
        'runtime' => $runtime,
        'safe_runtime' => $safeRuntime,
        'hires_runtime' => $hiresRuntime,
        'selected' => ($selectedRendererId !== null && $selectedRendererId === $rendererId),
        'selected_stream' => $selectedStream,
        'config_version' => $configVersion,
        'safe_playlist_exists' => $safePlaylistExists,
        'hires_playlist_exists' => $hiresPlaylistExists,
    ];
}

function gee_badge(bool $value, string $yes = 'Yes', string $no = 'No'): string
{
    $class = $value ? 'badge ok' : 'badge no';
    $label = $value ? $yes : $no;

    return '<span class="' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

function gee_value(?string $value): string
{
    $value = trim((string)$value);
    return $value !== ''
        ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        : '<span class="muted">—</span>';
}

function gee_stream_block(?array $runtime, string $streamKey, string $rendererId, bool $isSelectedRenderer, string $selectedStream, bool $playlistExists): string
{
    $isSelectedStream = $isSelectedRenderer && $selectedStream === $streamKey;

    $port = is_array($runtime) ? (string)($runtime['mpd_port'] ?? '') : '';
    $format = is_array($runtime) ? (string)($runtime['stream_format'] ?? '') : '';
    $fifo = is_array($runtime) ? (string)($runtime['fifo_path'] ?? '') : '';
    $playlist = is_array($runtime) ? (string)($runtime['playlist_path'] ?? '') : '';
    $runtimeConf = is_array($runtime) ? (string)($runtime['runtime_conf_path'] ?? '') : '';
    $portSource = is_array($runtime) ? (string)($runtime['mpd_port_source'] ?? '') : '';

    $html = '<div class="stream-block">';
    $html .= '<div class="stream-head">';
    $html .= '<div class="stream-title">' . htmlspecialchars(ucfirst($streamKey), ENT_QUOTES, 'UTF-8') . '</div>';

    if ($isSelectedStream) {
        $html .= '<div class="selected-tag">Active Stream</div>';
    }

    $html .= '</div>';
    $html .= '<table class="meta">';
    $html .= '<tr><td>MPD Port</td><td>' . gee_value($port) . '</td></tr>';
    $html .= '<tr><td>Port Source</td><td>' . gee_value($portSource) . '</td></tr>';
    $html .= '<tr><td>Format</td><td>' . gee_value($format) . '</td></tr>';
    $html .= '<tr><td>FIFO</td><td>' . gee_value($fifo) . '</td></tr>';
    $html .= '<tr><td>Playlist</td><td>' . gee_value($playlist) . '</td></tr>';
    $html .= '<tr><td>Playlist Exists</td><td>' . gee_badge($playlistExists) . '</td></tr>';
    $html .= '<tr><td>Runtime Conf</td><td>' . gee_value($runtimeConf) . '</td></tr>';
    $html .= '</table>';

    $html .= '<div class="actions">';
    $html .= '<a class="btn primary" href="/renderers.php?select='
        . rawurlencode($rendererId)
        . '&stream='
        . rawurlencode($streamKey)
        . '">Select '
        . htmlspecialchars(ucfirst($streamKey), ENT_QUOTES, 'UTF-8')
        . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gee Core - Registered Renderers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #050505;
            color: #f2f2f2;
            font: 14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .wrap {
            max-width: 1440px;
            margin: 0 auto;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .sub {
            color: #aaaaaa;
            margin-bottom: 20px;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .toolbar-left,
        .toolbar-right {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #111111;
            border: 1px solid #222222;
            color: #dddddd;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid #333333;
            background: #101010;
            color: #ffffff;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #181818;
        }

        .btn.primary {
            border-color: #666666;
        }

        .notice {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #2a2a2a;
            background: #101010;
        }

        .notice.success {
            border-color: #275c39;
            background: #0d1710;
            color: #d6f5df;
        }

        .notice.error {
            border-color: #6a2a2a;
            background: #190e0e;
            color: #ffd7d7;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
            gap: 18px;
            align-items: start;
        }

        .card {
            background: #0c0c0c;
            border: 1px solid #202020;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            min-width: 0;
            overflow: hidden;
        }

        .card.selected {
            border-color: #4e4e4e;
        }

        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
        }

        .subtitle {
            color: #9c9c9c;
            font-size: 13px;
            margin-top: 2px;
        }

        .selected-tag {
            padding: 5px 10px;
            border-radius: 999px;
            background: #1d1d1d;
            border: 1px solid #3a3a3a;
            font-size: 12px;
            white-space: nowrap;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            vertical-align: top;
            padding: 7px 0;
            border-bottom: 1px solid #191919;
        }

        .meta td:first-child {
            width: 42%;
            color: #9e9e9e;
            padding-right: 12px;
        }

        .meta td:last-child {
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .meta tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid #2a2a2a;
        }

        .badge.ok {
            background: #102014;
            color: #cbf5d5;
            border-color: #24442d;
        }

        .badge.no {
            background: #241111;
            color: #ffd6d6;
            border-color: #4e2424;
        }

        .muted {
            color: #777777;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .stream-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-top: 16px;
        }

        .stream-block {
            background: #090909;
            border: 1px solid #1d1d1d;
            border-radius: 14px;
            padding: 14px;
        }

        .stream-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stream-title {
            font-size: 16px;
            font-weight: 600;
        }

        .footer-note {
            margin-top: 20px;
            color: #888888;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            body {
                padding: 14px;
            }

            .card {
                padding: 14px;
            }

            .meta td:first-child {
                width: 46%;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Registered Renderers</h1>
    <div class="sub">Gee Core renderer status and runtime diagnostics</div>

    <div class="toolbar">
        <div class="toolbar-left">
            <span class="pill">Count: <?= count($rows) ?></span>
            <span class="pill">Selected Renderer: <?= gee_value($selectedRendererLabel) ?></span>
            <span class="pill">Selected Stream: <?= gee_value($selectedStream) ?></span>
        </div>
        <div class="toolbar-right">
            <a class="btn" href="/renderers.php">Refresh</a>
            <a class="btn" href="/">Back to Player</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="notice <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if ($rows === []): ?>
        <div class="card">
            <div class="title">No renderers registered</div>
            <div class="subtitle">Register a renderer, then refresh this page.</div>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($rows as $row): ?>
                <?php
                    $renderer = $row['renderer'];
                    $runtime = $row['runtime'];

                    $rendererId = (string)($renderer['renderer_id'] ?? '');
                    $rendererName = (string)($renderer['renderer_name'] ?? '');
                    $displayName = (string)($renderer['display_name'] ?? '');
                    $cardTitle = $rendererName !== '' ? $rendererName : ($displayName !== '' ? $displayName : $rendererId);
                ?>
                <div class="card<?= $row['selected'] ? ' selected' : '' ?>">
                    <div class="card-head">
                        <div>
                            <div class="title"><?= htmlspecialchars($cardTitle, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="subtitle">Renderer ID: <?= gee_value($rendererId) ?></div>
                        </div>

                        <?php if ($row['selected']): ?>
                            <div class="selected-tag">Selected Renderer</div>
                        <?php endif; ?>
                    </div>

                    <table class="meta">
                        <tr>
                            <td>Hostname</td>
                            <td><?= gee_value((string)($renderer['hostname'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td>IP Address</td>
                            <td><?= gee_value((string)($renderer['ip_address'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td>MAC Address</td>
                            <td><?= gee_value((string)($renderer['mac_address'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td>Model</td>
                            <td><?= gee_value((string)($renderer['model'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td>Installer Version</td>
                            <td><?= gee_value((string)($renderer['installer_version'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td>Config Version</td>
                            <td><?= gee_value($row['config_version']) ?></td>
                        </tr>
                        <tr>
                            <td>Runtime Ready</td>
                            <td><?= gee_badge(is_array($runtime) ? (bool)($runtime['runtime_ready'] ?? false) : false) ?></td>
                        </tr>
                        <tr>
                            <td>Runtime Directory</td>
                            <td><?= gee_value(is_array($runtime) ? (string)($runtime['runtime_dir'] ?? '') : '') ?></td>
                        </tr>
                    </table>

                    <div class="stream-grid">
                        <?= gee_stream_block($row['safe_runtime'], 'safe', $rendererId, $row['selected'], $row['selected_stream'], (bool)$row['safe_playlist_exists']) ?>
                        <?= gee_stream_block($row['hires_runtime'], 'hires', $rendererId, $row['selected'], $row['selected_stream'], (bool)$row['hires_playlist_exists']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<!--    <div class="footer-note">
        This page is intended as the renderer and stream diagnostics view before full player integration.
    </div>-->
</div>
</body>
</html>