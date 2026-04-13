<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/css/gee.css">
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
    display: block;
    margin: 0 auto;
}
#renderer {
    margin-top: 20px;
    font-size: 0.95rem;
    opacity: 0.85;
    letter-spacing: 0.08em;
}
#status {
    margin-top: 8px;
    font-size: 0.9rem;
    opacity: 0.75;
}
#title {
    font-size: 2rem;
    margin-top: 20px;
    min-height: 2.4rem;
}
#artist, #album {
    font-size: 1.2rem;
    margin-top: 10px;
    min-height: 1.5rem;
}
#progress-wrap, #volume-wrap {
    margin-top: 24px;
}
progress {
    width: 100%;
    max-width: 500px;
}
#controls, #secondary-controls, #playlist-controls {
    margin-top: 24px;
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
button {
    background: #111;
    color: #fff;
    border: 1px solid #444;
    padding: 12px 18px;
    cursor: pointer;
    min-width: 90px;
}
button:hover {
    background: #1a1a1a;
}
.idle {
    opacity: 0.6;
}
</style>
</head>
<body>
<div id="player">
    <div id="artwork">
        <img id="cover" src="/img/black.jpg" alt="Artwork">
    </div>

    <div id="renderer">LOADING...</div>
    <div id="status">Connecting...</div>

    <div id="title">Loading...</div>
    <div id="artist"></div>
    <div id="album"></div>

    <div id="playlist-controls">
        <button type="button" onclick="loadMusic()">Load Music</button>
    </div>

    <div id="progress-wrap">
        <div><span id="elapsed">0:00</span> / <span id="duration">0:00</span></div>
        <progress id="progress" value="0" max="100"></progress>
    </div>

    <div id="secondary-controls">
        <button type="button" onclick="sendCommand(13)">Restart Track</button>
        <button type="button" onclick="changeVolume(-5)">Vol -</button>
        <button type="button" onclick="changeVolume(5)">Vol +</button>
    </div>

    <div id="controls">
        <button type="button" onclick="sendCommand(3)">Prev</button>
        <button type="button" id="playPauseButton" onclick="sendCommand(2)">Play</button>
        <button type="button" onclick="sendCommand(4)">Next</button>
    </div>

    <div id="volume-wrap">
        <div>Volume: <span id="volume">0</span></div>
        <progress id="volume-bar" value="0" max="100"></progress>
    </div>
</div>

<script>
const DEFAULT_COVER = '/img/black.jpg';

function fmt(sec) {
    sec = parseInt(sec || 0, 10);
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function setIdleState(rendererName = '') {
    document.getElementById('renderer').innerText = rendererName || 'NO RENDERER';
    document.getElementById('status').innerText = 'Stopped';
    document.getElementById('title').innerText = 'Nothing playing';
    document.getElementById('artist').innerText = '';
    document.getElementById('album').innerText = '';
    document.getElementById('elapsed').innerText = '0:00';
    document.getElementById('duration').innerText = '0:00';
    document.getElementById('progress').value = 0;
    document.getElementById('cover').src = DEFAULT_COVER;
    document.getElementById('playPauseButton').innerText = 'Play';
    document.getElementById('player').classList.add('idle');
}

function applyPlaybackState(state) {
    const button = document.getElementById('playPauseButton');
    const status = document.getElementById('status');

    if (state === 'play') {
        button.innerText = 'Pause';
        status.innerText = 'Playing';
        document.getElementById('player').classList.remove('idle');
        return;
    }

    if (state === 'pause') {
        button.innerText = 'Play';
        status.innerText = 'Paused';
        document.getElementById('player').classList.remove('idle');
        return;
    }

    button.innerText = 'Play';
    status.innerText = 'Stopped';
    document.getElementById('player').classList.add('idle');
}

async function fetchMeta() {
    try {
        const res = await fetch('/api/?service=1', { cache: 'no-store' });
        const data = await res.json();
        updateUI(data);
    } catch (err) {
        console.error('fetchMeta failed', err);
        document.getElementById('status').innerText = 'Connection error';
    }
}

function updateUI(data) {
    if (!data || data.status !== 'ok') {
        return;
    }

    document.getElementById('renderer').innerText = data.renderer_display || '';

    const title = data.title || '';
    const artist = data.artist || '';
    const album = data.album || '';
    const state = data.state || 'stop';

    if (!title && !artist && !album && state === 'stop') {
        setIdleState(data.renderer_display || '');
    } else {
        document.getElementById('title').innerText = title || 'Unknown Title';
        document.getElementById('artist').innerText = artist || '';
        document.getElementById('album').innerText = album || '';
    }

    if (data.image) {
        document.getElementById('cover').src = data.image;
    } else if (!title && !artist && !album) {
        document.getElementById('cover').src = DEFAULT_COVER;
    }

    const elapsed = parseInt(data.elapsed || 0, 10);
    const duration = parseInt(data.duration || 0, 10);
    const progress = duration > 0 ? Math.round((elapsed / duration) * 100) : 0;

    document.getElementById('elapsed').innerText = fmt(elapsed);
    document.getElementById('duration').innerText = fmt(duration);
    document.getElementById('progress').value = progress;

    const volume = parseInt(data.volume ?? 0, 10);
    document.getElementById('volume').innerText = volume;
    document.getElementById('volume-bar').value = volume;

    applyPlaybackState(state);
}

async function sendCommand(service) {
    try {
        await fetch(`/api/?service=${service}`, { cache: 'no-store' });
        await fetchMeta();
    } catch (err) {
        console.error('sendCommand failed', err);
    }
}

async function changeVolume(delta) {
    try {
        await fetch(`/api/?service=15&mod=${delta}`, { cache: 'no-store' });
        await fetchMeta();
    } catch (err) {
        console.error('changeVolume failed', err);
    }
}

async function loadMusic() {
    try {
        const res = await fetch('/api/?service=5', { cache: 'no-store' });
        const data = await res.json();

        if (data.status !== 'ok') {
            console.error('loadMusic failed', data);
            return;
        }

        await fetchMeta();
    } catch (err) {
        console.error('loadMusic failed', err);
    }
}

setInterval(fetchMeta, 5000);
fetchMeta();
</script>
</body>
</html>