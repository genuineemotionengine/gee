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
    max-width: 760px;
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
    opacity: 0.9;
    letter-spacing: 0.08em;
}
#stream {
    margin-top: 6px;
    font-size: 0.9rem;
    opacity: 0.8;
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
#selectors,
#progress-wrap,
#volume-wrap {
    margin-top: 24px;
}
#selectors {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}
.selector-block {
    background: #0d0d0d;
    border: 1px solid #222;
    padding: 12px;
    text-align: left;
}
.selector-block label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.85rem;
    opacity: 0.8;
    letter-spacing: 0.06em;
}
select {
    width: 100%;
    background: #111;
    color: #fff;
    border: 1px solid #444;
    padding: 10px;
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
#message {
    margin-top: 16px;
    min-height: 1.3rem;
    font-size: 0.9rem;
    opacity: 0.8;
}
@media (max-width: 640px) {
    #selectors {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<div id="player">
    <div id="artwork">
        <img id="cover" src="/img/black.jpg" alt="Artwork">
    </div>

    <div id="renderer">LOADING...</div>
    <div id="stream">STREAM: --</div>
    <div id="status">Connecting...</div>

    <div id="selectors">
        <div class="selector-block">
            <label for="rendererSelect">RENDERER</label>
            <select id="rendererSelect"></select>
        </div>
        <div class="selector-block">
            <label for="streamSelect">STREAM</label>
            <select id="streamSelect">
                <option value="safe">Safe</option>
                <option value="hires">Hires</option>
            </select>
        </div>
    </div>

    <div id="message"></div>

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
let rendererList = [];
let isUpdatingSelectors = false;

function fmt(sec) {
    sec = parseInt(sec || 0, 10);
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

function setMessage(text = '') {
    document.getElementById('message').innerText = text;
}

function setIdleState(rendererName = '', streamName = '') {
    document.getElementById('renderer').innerText = rendererName || 'NO RENDERER';
    document.getElementById('stream').innerText = streamName ? `STREAM: ${streamName}` : 'STREAM: --';
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

async function fetchRendererList() {
    try {
        const res = await fetch('/api/?service=20', { cache: 'no-store' });
        const data = await res.json();

        if (!data || data.status !== 'ok' || !Array.isArray(data.renderers)) {
            return;
        }

        rendererList = data.renderers;
        populateRendererSelect(data.selected_renderer_id || '');
        setStreamSelect(data.selected_stream || 'safe');
    } catch (err) {
        console.error('fetchRendererList failed', err);
    }
}

function populateRendererSelect(selectedRendererId = '') {
    const select = document.getElementById('rendererSelect');
    const previous = select.value;

    isUpdatingSelectors = true;
    select.innerHTML = '';

    if (!rendererList.length) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No renderers';
        select.appendChild(option);
        isUpdatingSelectors = false;
        return;
    }

    rendererList.forEach(renderer => {
        const option = document.createElement('option');
        option.value = renderer.renderer_id || '';
        option.textContent = renderer.display_name || renderer.renderer_name || renderer.renderer_id || 'Unknown';
        select.appendChild(option);
    });

    const finalValue = selectedRendererId || previous || (rendererList[0]?.renderer_id ?? '');
    select.value = finalValue;

    isUpdatingSelectors = false;
}

function setStreamSelect(streamKey = 'safe') {
    const select = document.getElementById('streamSelect');
    isUpdatingSelectors = true;
    select.value = (streamKey === 'hires') ? 'hires' : 'safe';
    isUpdatingSelectors = false;
}

async function selectRenderer(rendererId) {
    if (!rendererId) {
        return;
    }

    try {
        setMessage('Switching renderer...');
        const res = await fetch(`/api/?service=21&renderer_id=${encodeURIComponent(rendererId)}`, { cache: 'no-store' });
        const data = await res.json();

        if (!data || data.status !== 'ok') {
            console.error('selectRenderer failed', data);
            setMessage('Renderer switch failed');
            return;
        }

        setMessage(`Renderer selected: ${data.renderer?.display_name || rendererId}`);
        await fetchRendererList();
        await fetchMeta();
    } catch (err) {
        console.error('selectRenderer failed', err);
        setMessage('Renderer switch failed');
    }
}

async function selectStream(streamKey) {
    if (!streamKey) {
        return;
    }

    try {
        setMessage('Switching stream...');
        const res = await fetch(`/api/?service=23&stream=${encodeURIComponent(streamKey)}`, { cache: 'no-store' });
        const data = await res.json();

        if (!data || data.status !== 'ok') {
            console.error('selectStream failed', data);
            setMessage('Stream switch failed');
            return;
        }

        setMessage(`Stream selected: ${streamKey}`);
        await fetchRendererList();
        await fetchMeta();
    } catch (err) {
        console.error('selectStream failed', err);
        setMessage('Stream switch failed');
    }
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

    const rendererDisplay = data.renderer_display || data.renderer_name || data.renderer_id || '';
    const streamDisplay = data.stream_key ? `STREAM: ${String(data.stream_key).toUpperCase()}` : 'STREAM: --';

    document.getElementById('renderer').innerText = rendererDisplay;
    document.getElementById('stream').innerText = streamDisplay;

    const title = data.title || '';
    const artist = data.artist || '';
    const album = data.album || '';
    const state = data.state || 'stop';

    if (!title && !artist && !album && state === 'stop') {
        setIdleState(rendererDisplay, streamDisplay);
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
            setMessage('Load Music failed');
            return;
        }

        setMessage('Music loaded');
        await fetchMeta();
    } catch (err) {
        console.error('loadMusic failed', err);
        setMessage('Load Music failed');
    }
}

document.getElementById('rendererSelect').addEventListener('change', async function () {
    if (isUpdatingSelectors) {
        return;
    }
    await selectRenderer(this.value);
});

document.getElementById('streamSelect').addEventListener('change', async function () {
    if (isUpdatingSelectors) {
        return;
    }
    await selectStream(this.value);
});

async function initialisePlayer() {
    await fetchRendererList();
    await fetchMeta();
    setMessage('');
}

setInterval(fetchMeta, 5000);
initialisePlayer();
</script>
</body>
</html>