<?php

declare(strict_types=1);

require_once '/var/www/app/core/bootstrap.php';

$conn = gee_db();

$cookieName = 'gee_selected_renderer';
$selectedRendererId = isset($_COOKIE[$cookieName]) ? (int) $_COOKIE[$cookieName] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['renderer_id']) && !isset($_POST['assign_stream'])) {
        $selectedRendererId = (int) $_POST['renderer_id'];

        setcookie(
            $cookieName,
            (string) $selectedRendererId,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );

        header('Location: /renderers.php');
        exit;
    }

    if (isset($_POST['assign_stream'], $_POST['renderer_id'], $_POST['stream_id'])) {
        $rendererId = (int) $_POST['renderer_id'];
        $streamId = (int) $_POST['stream_id'];
        $selectedRendererId = $rendererId;

        $setCookie = setcookie(
            $cookieName,
            (string) $selectedRendererId,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );

        $stmt = $conn->prepare("
            INSERT INTO renderer_stream_map (renderer_id, stream_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
                stream_id = VALUES(stream_id),
                updated_at = CURRENT_TIMESTAMP
        ");

        if (!$stmt) {
            http_response_code(500);
            echo 'Failed to prepare stream assignment query: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
            exit;
        }

        $stmt->bind_param('ii', $rendererId, $streamId);

        if (!$stmt->execute()) {
            http_response_code(500);
            echo 'Failed to assign stream: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
            $stmt->close();
            exit;
        }

        $stmt->close();

        header('Location: /renderers.php');
        exit;
    }
}

$streamsResult = $conn->query("
    SELECT id, stream_key, stream_name, stream_format, fifo_path, is_active
    FROM streams
    ORDER BY stream_name ASC
");

if (!$streamsResult) {
    http_response_code(500);
    echo 'Failed to load streams: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
    exit;
}

$streams = [];
while ($row = $streamsResult->fetch_assoc()) {
    $streams[] = $row;
}

$sql = "
    SELECT
        r.id AS renderer_id,
        r.hostname,
        r.ip,
        r.audio_profile,
        r.alsa_device,
        r.max_sample_rate,
        r.bit_depth,
        r.channels,
        r.quality_tier,
        r.preferred_stream_format,
        r.status,
        r.room,
        r.display_name,
        r.is_active,
        s.id AS stream_id,
        s.stream_key,
        s.stream_name,
        s.stream_format,
        s.fifo_path,
        s.audio_source_type,
        s.is_active AS stream_active
    FROM renderers r
    LEFT JOIN renderer_stream_map rsm ON r.id = rsm.renderer_id
    LEFT JOIN streams s ON rsm.stream_id = s.id
    ORDER BY
        COALESCE(r.display_name, r.hostname) ASC
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo 'Database query failed: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
    exit;
}

$renderers = [];

while ($row = $result->fetch_assoc()) {
    $renderers[] = $row;
}

if (empty($renderers)) {
    $selectedRenderer = null;
} else {
    $selectedRenderer = null;

    foreach ($renderers as $renderer) {
        if ((int)$renderer['renderer_id'] === $selectedRendererId) {
            $selectedRenderer = $renderer;
            break;
        }
    }

    if ($selectedRenderer === null) {
        $selectedRenderer = $renderers[0];
        $selectedRendererId = (int)$selectedRenderer['renderer_id'];

        setcookie(
            $cookieName,
            (string) $selectedRendererId,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]
        );
    }
}

function e(null|string|int $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayRendererName(array $renderer): string
{
    return !empty($renderer['display_name']) ? $renderer['display_name'] : $renderer['hostname'];
}

function yesNo($value): string
{
    return ((int)$value === 1) ? 'Yes' : 'No';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gee Renderers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            background: #111;
            color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1, h2 {
            margin-top: 0;
        }

        .panel {
            background: #1b1b1b;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .selector-form,
        .stream-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        select, button {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #222;
            color: #fff;
            font-size: 14px;
        }

        button {
            cursor: pointer;
        }

        .context-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .context-item {
            background: #222;
            border-radius: 10px;
            padding: 14px;
            border: 1px solid #333;
        }

        .context-label {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .context-value {
            font-size: 15px;
            font-weight: bold;
            word-break: break-word;
        }

        .assignment-box {
            margin-top: 18px;
            padding: 16px;
            background: #222;
            border: 1px solid #333;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #333;
            vertical-align: top;
        }

        th {
            color: #bbb;
            font-size: 13px;
        }

        .selected-row {
            background: #202a36;
        }

        .muted {
            color: #999;
        }

        .empty {
            color: #bbb;
            font-style: italic;
        }

        .small {
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="panel">
        <h1>Renderer Context</h1>

        <?php if (!empty($renderers)): ?>
            <form method="post" class="selector-form">
                <label for="renderer_id">Now controlling:</label>
                <select name="renderer_id" id="renderer_id">
                    <?php foreach ($renderers as $renderer): ?>
                        <option value="<?= (int)$renderer['renderer_id'] ?>"
                            <?= ((int)$renderer['renderer_id'] === $selectedRendererId) ? 'selected' : '' ?>>
                            <?= e(displayRendererName($renderer)) ?>
                            <?php if (!empty($renderer['room'])): ?>
                                — <?= e($renderer['room']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Switch Renderer</button>
            </form>
        <?php else: ?>
            <p class="empty">No renderers have registered yet.</p>
        <?php endif; ?>

        <?php if ($selectedRenderer !== null): ?>
            <div class="context-grid">
                <div class="context-item">
                    <div class="context-label">Renderer</div>
                    <div class="context-value"><?= e(displayRendererName($selectedRenderer)) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Hostname</div>
                    <div class="context-value"><?= e($selectedRenderer['hostname']) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Room</div>
                    <div class="context-value"><?= $selectedRenderer['room'] ? e($selectedRenderer['room']) : '<span class="muted">Not set</span>' ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Status</div>
                    <div class="context-value"><?= e($selectedRenderer['status']) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Audio Profile</div>
                    <div class="context-value"><?= e($selectedRenderer['audio_profile']) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Preferred Format</div>
                    <div class="context-value"><?= e($selectedRenderer['preferred_stream_format']) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">Assigned Stream</div>
                    <div class="context-value">
                        <?= $selectedRenderer['stream_name'] ? e($selectedRenderer['stream_name']) : '<span class="muted">Not assigned</span>' ?>
                    </div>
                </div>

                <div class="context-item">
                    <div class="context-label">Stream Format</div>
                    <div class="context-value">
                        <?= $selectedRenderer['stream_format'] ? e($selectedRenderer['stream_format']) : '<span class="muted">Not assigned</span>' ?>
                    </div>
                </div>

                <div class="context-item">
                    <div class="context-label">ALSA Device</div>
                    <div class="context-value"><?= e($selectedRenderer['alsa_device']) ?></div>
                </div>

                <div class="context-item">
                    <div class="context-label">IP Address</div>
                    <div class="context-value"><?= e($selectedRenderer['ip']) ?></div>
                </div>
            </div>

            <div class="assignment-box">
                <h2 style="margin-bottom: 10px;">Assign Stream</h2>
                <form method="post" class="stream-form">
                    <input type="hidden" name="assign_stream" value="1">
                    <input type="hidden" name="renderer_id" value="<?= (int)$selectedRenderer['renderer_id'] ?>">

                    <label for="stream_id">Stream for <?= e(displayRendererName($selectedRenderer)) ?>:</label>
                    <select name="stream_id" id="stream_id">
                        <?php foreach ($streams as $stream): ?>
                            <option value="<?= (int)$stream['id'] ?>"
                                <?= ((int)$stream['id'] === (int)$selectedRenderer['stream_id']) ? 'selected' : '' ?>>
                                <?= e($stream['stream_name']) ?> — <?= e($stream['stream_format']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Save Assignment</button>
                </form>
                <p class="small">This updates the renderer-to-stream mapping in Gee Core. Audio engine orchestration comes next.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>All Renderers</h2>

        <?php if (empty($renderers)): ?>
            <p class="empty">No renderer records found.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Renderer</th>
                    <th>Room</th>
                    <th>Profile</th>
                    <th>Preferred</th>
                    <th>Assigned Stream</th>
                    <th>Stream Format</th>
                    <th>Status</th>
                    <th>Active</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($renderers as $renderer): ?>
                    <tr class="<?= ((int)$renderer['renderer_id'] === $selectedRendererId) ? 'selected-row' : '' ?>">
                        <td>
                            <strong><?= e(displayRendererName($renderer)) ?></strong><br>
                            <span class="muted"><?= e($renderer['hostname']) ?></span>
                        </td>
                        <td><?= $renderer['room'] ? e($renderer['room']) : '<span class="muted">—</span>' ?></td>
                        <td><?= e($renderer['audio_profile']) ?></td>
                        <td><?= e($renderer['preferred_stream_format']) ?></td>
                        <td><?= $renderer['stream_name'] ? e($renderer['stream_name']) : '<span class="muted">—</span>' ?></td>
                        <td><?= $renderer['stream_format'] ? e($renderer['stream_format']) : '<span class="muted">—</span>' ?></td>
                        <td><?= e($renderer['status']) ?></td>
                        <td><?= yesNo($renderer['is_active']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>