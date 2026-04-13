<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/css/gee1.css">
<style>
body {
    background: #000;
    color: #fff;
    margin: 0;
    font-family: Arial, sans-serif;
    text-align: center;
}
#player {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}
#artwork img {
    width: 100%;
    max-width: 500px;
    background: #111;
}
#title { font-size: 2rem; margin-top: 20px; }
#artist, #album { font-size: 1.2rem; margin-top: 10px; }
#renderer { margin-top: 20px; font-size: 0.9rem; opacity: 0.8; }
#controls {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 12px;
}
button {
    background: #111;
    color: #fff;
    border: 1px solid #444;
    padding: 12px 18px;
    cursor: pointer;
}
#progress-wrap, #volume-wrap {
    margin-top: 24px;
}
progress {
    width: 100%;
    max-width: 500px;
}
</style>
</head>
<body>
<div id="player">
    <div id="artwork">
        <img id="cover" src="/img/black.jpg" alt="Artwork">
    </div>

    <div id="renderer"></div>
    <div id="title">Loading...</div>
    <div id="artist"></div>
    <div id="album"></div>

    <div id="progress-wrap">
        <div><span id="elapsed">0:00</span> / <span id="duration">0:00</span></div>
        <progress id="progress" value="0" max="100"></progress>
    </div>

    <div id="volume-wrap">
        <div>Volume: <span id="volume">0</span></div>
        <progress id="volume-bar" value="0" max="100"></progress>
    </div>

    <div id="controls">
        <button onclick="sendCommand(3)">Prev</button>
        <button onclick="sendCommand(2)">Play/Pause</button>
        <button onclick="sendCommand(4)">Next</button>
    </div>
</div>

<script>
function fmt(sec) {
    sec = parseInt(sec || 0, 10);
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

async function fetchMeta() {
    try {
        const res = await fetch('/api/?service=1');
        const data = await res.json();
        updateUI(data);
    } catch (err) {
        console.error('fetchMeta failed', err);
    }
}

function updateUI(data) {
    if (!data || data.status !== 'ok') {
        return;
    }

    document.getElementById('renderer').innerText = data.renderer_display || '';
    document.getElementById('title').innerText = data.title || '';
    document.getElementById('artist').innerText = data.artist || '';
    document.getElementById('album').innerText = data.album || '';

    if (data.image) {
        document.getElementById('cover').src = data.image;
    }

    const elapsed = parseInt(data.elapsed || 0, 10);
    const duration = parseInt(data.duration || 0, 10);
    const progress = duration > 0 ? Math.round((elapsed / duration) * 100) : 0;

    document.getElementById('elapsed').innerText = fmt(elapsed);
    document.getElementById('duration').innerText = fmt(duration);
    document.getElementById('progress').value = progress;

    document.getElementById('volume').innerText = data.volume ?? 0;
    document.getElementById('volume-bar').value = data.volume ?? 0;
}

async function sendCommand(service) {
    try {
        await fetch(`/api/?service=${service}`);
        fetchMeta();
    } catch (err) {
        console.error('sendCommand failed', err);
    }
}

setInterval(fetchMeta, 5000);
fetchMeta();
</script>
</body>
</html>