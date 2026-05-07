<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/renderers.php';
require_once __DIR__ . '/../core/runtime.php';

$message = '';
$messageType = 'info';

function gee_switch_renderer_stream_runtime(string $rendererId, string $streamKey): bool
{
    $rendererId = gee_safe_renderer_id($rendererId);
    $streamKey = trim($streamKey);

    if ($rendererId === '' || !gee_is_valid_stream_key($streamKey)) {
        return false;
    }

    $switchScript = '/usr/local/bin/gee-switch-renderer-stream.sh';

    if (!is_file($switchScript) || !is_executable($switchScript)) {
        return false;
    }

    $command = escapeshellarg($switchScript)
        . ' '
        . escapeshellarg($rendererId)
        . ' '
        . escapeshellarg($streamKey)
        . ' 2>&1';

    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    return $exitCode === 0;
}

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

        $switchOk = true;

        if ($rendererOk && $streamOk && $selectStream !== '' && gee_is_valid_stream_key($selectStream)) {
            $switchOk = gee_switch_renderer_stream_runtime($selectRendererId, $selectStream);
        }

        if ($rendererOk && $streamOk && $switchOk) {
            $url = '/renderers.php?selected=' . rawurlencode($selectRendererId);
            if ($selectStream !== '' && gee_is_valid_stream_key($selectStream)) {
                $url .= '&stream_selected=' . rawurlencode($selectStream);
            }
            header('Location: ' . $url);
            exit;
        }

        if (!$switchOk) {
            $message = 'Failed to switch renderer stream.';
            $messageType = 'error';
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
            $activeRendererId = gee_get_selected_renderer_id();
            $switchOk = $activeRendererId !== null
                ? gee_switch_renderer_stream_runtime($activeRendererId, $selectStream)
                : true;

            if ($switchOk) {
                header('Location: /renderers.php?stream_selected=' . rawurlencode($selectStream));
                exit;
            }

            $message = 'Failed to switch renderer stream.';
            $messageType = 'error';
        } else {
            $message = 'Failed to set selected stream cookie.';
            $messageType = 'error';
        }
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

function gee_renderer_display_label(array $rendererContext): string
{
    $rendererId = (string)($rendererContext['renderer_id'] ?? '');
    $rendererName = (string)($rendererContext['renderer_name'] ?? '');
    $displayName = (string)($rendererContext['display_name'] ?? '');
    $hostname = (string)($rendererContext['hostname'] ?? '');

    return $rendererName !== ''
        ? $rendererName
        : ($displayName !== ''
            ? $displayName
            : ($hostname !== '' ? $hostname : $rendererId));
}

function gee_title_label(string $value): string
{
    $value = trim($value);
    return $value !== '' ? ucfirst($value) : '';
}

foreach ($renderers as $rendererContext) {
    $rendererId = (string)($rendererContext['renderer_id'] ?? '');

    if ($selectedRendererId !== null && $rendererId === $selectedRendererId) {
        $selectedRendererLabel = gee_renderer_display_label($rendererContext);

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

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            padding: 18px;
            min-height: 100dvh;
            background: #050505;
            color: #f2f2f2;
            font: 14px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            overflow-x: hidden;
        }

        .wrap {
            width: 100%;
            max-width: 980px;
            min-height: calc(100dvh - 36px);
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

        .current-selection {
            margin: 14px 0 18px;
            font-size: 17px;
            color: #f2f2f2;
        }

        .current-selection strong {
            font-weight: 600;
        }

        .current-selection .accent {
            color: #249cff;
            font-weight: 600;
        }

        .renderer-switcher {
            margin: 0 0 22px;
            border: 1px solid #242424;
            border-radius: 18px;
            overflow: hidden;
            background: rgba(10,10,10,0.72);
        }

        .renderer-switch-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(86px, 112px) minmax(86px, 112px);
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid #1c1c1c;
        }

        .renderer-switch-row:last-child {
            border-bottom: 0;
        }

        .renderer-switch-name {
            font-size: 17px;
            font-weight: 600;
            color: #f4f4f4;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .stream-choice {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-width: 0;
            min-height: 44px;
            text-align: center;
            padding: 9px 10px;
            border-radius: 999px;
            border: 1px solid #333333;
            background: #101010;
            color: #ffffff;
            text-decoration: none;
            line-height: 1.1;
            white-space: nowrap;
        }

        .stream-choice:hover {
            background: #181818;
        }

        .stream-choice.active {
            border-color: #249cff;
            background: #249cff;
            color: #ffffff;
            box-shadow: 0 0 24px rgba(36,156,255,0.25);
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
                padding: 12px;
            }

            .wrap {
                min-height: calc(100dvh - 24px);
            }

            h1 {
                font-size: 26px;
            }

            .card {
                padding: 14px;
            }

            .meta td:first-child {
                width: 46%;
            }

            .renderer-switch-row {
                grid-template-columns: minmax(0, 1fr) minmax(80px, 104px) minmax(80px, 104px);
                gap: 8px;
                padding: 11px 10px;
            }

            .renderer-switch-name {
                font-size: 16px;
            }

            .stream-choice {
                min-height: 42px;
                padding: 8px 8px;
            }
        }

        @media (max-width: 430px) {
            body {
                padding: 10px;
            }

            .wrap {
                min-height: calc(100dvh - 20px);
            }

            .renderer-switch-row {
                grid-template-columns: minmax(0, 1fr) minmax(74px, 96px) minmax(74px, 96px);
                gap: 7px;
                padding: 10px 8px;
            }

            .renderer-switch-name {
                font-size: 15px;
            }

            .stream-choice {
                font-size: 14px;
                min-height: 40px;
                padding: 7px 6px;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Registered Renderers</h1>
    <div class="sub">Gee Core renderer status and runtime diagnostics</div>

    <?php
        $currentRendererText = $selectedRendererLabel !== '' ? gee_title_label($selectedRendererLabel) : 'No renderer selected';
        $currentStreamText = $selectedStream !== '' ? gee_title_label($selectedStream) : 'No stream selected';
    ?>
    <div class="current-selection">
        <strong>Current Renderer:</strong>
        <span class="accent"><?= htmlspecialchars($currentRendererText, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($currentStreamText, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <?php if ($rows !== []): ?>
        <div class="renderer-switcher" aria-label="Renderer stream selection">
            <?php foreach ($rows as $row): ?>
                <?php
                    $renderer = $row['renderer'];
                    $rendererId = (string)($renderer['renderer_id'] ?? '');
                    $label = gee_renderer_display_label($renderer);
                    $safeActive = (bool)$row['selected'] && $selectedStream === 'safe';
                    $hiresActive = (bool)$row['selected'] && $selectedStream === 'hires';
                ?>
                <div class="renderer-switch-row">
                    <div class="renderer-switch-name"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>:</div>
                    <a class="stream-choice<?= $safeActive ? ' active' : '' ?>" href="/renderers.php?select=<?= rawurlencode($rendererId) ?>&stream=safe">Safe</a>
                    <a class="stream-choice<?= $hiresActive ? ' active' : '' ?>" href="/renderers.php?select=<?= rawurlencode($rendererId) ?>&stream=hires">Hires</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
                    $cardTitle = gee_renderer_display_label($renderer);
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
<script>
(function () {
    const refreshMs = 10000;
    let timer = null;

    function scheduleRefresh() {
        if (timer) {
            window.clearTimeout(timer);
        }

        timer = window.setTimeout(function () {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            } else {
                scheduleRefresh();
            }
        }, refreshMs);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            scheduleRefresh();
        }
    });

    scheduleRefresh();
}());
</script>
</body>
</html>