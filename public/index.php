<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
<meta name="theme-color" content="#000000">
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/css/gee.css?v=20260420f">
</head>
<body>
<div id="app">
    <div class="player-shell">
        <div id="player" class="player idle state-stop">
            <div class="hero">
                <div class="art-frame">
                    <div class="art-stage">
                        <img id="cover" src="/img/black.jpg" alt="Artwork">

                        <div class="art-grid" aria-label="Player controls">
                            <button type="button" class="zone" data-zone="refresh" title="Refresh"></button>
                            <button type="button" class="zone" data-zone="more" title="More"></button>
                            <button type="button" class="zone" data-zone="load" title="Load Music"></button>

                            <button type="button" class="zone disabled" tabindex="-1" aria-hidden="true"></button>
                            <button type="button" class="zone" data-zone="playpause" title="Play / Pause"></button>
                            <button type="button" class="zone disabled" tabindex="-1" aria-hidden="true"></button>

                            <button type="button" class="zone" data-zone="prev" title="Previous"></button>
                            <button type="button" class="zone" data-zone="restart" title="Restart Track"></button>
                            <button type="button" class="zone" data-zone="next" title="Next"></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="player-body">
                <div class="context-line">
                    <span id="renderer" class="context-renderer">Loading…</span>
                    <span class="context-sep">·</span>
                    <span id="stream" class="context-stream">--</span>
                </div>

                <div class="progress-wrap">
                    <div class="track-bar" role="progressbar" aria-label="Track progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div id="progressFill" class="track-fill"></div>
                    </div>

                    <div class="time-row">
                        <span id="elapsed">0:00</span>
                        <span id="duration">0:00</span>
                    </div>
                </div>

                <div class="transport-panel">
                    <div class="volume-panel">
                        <div class="volume-bar-wrap">
                            <button type="button" class="volume-step" id="volDownButton" title="Volume down" aria-label="Volume down">−</button>

                            <div id="volumeBar" class="volume-bar" role="progressbar" aria-label="Volume" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div id="volumeFill" class="volume-fill"></div>
                            </div>

                            <button type="button" class="volume-step" id="volUpButton" title="Volume up" aria-label="Volume up">+</button>
                        </div>
                    </div>
                </div>

                <div class="meta">
                    <div id="title" class="meta-primary">Loading…</div>
                    <div id="artist" class="meta-secondary"></div>
                    <div id="album" class="meta-tertiary"></div>
                </div>

                <div id="message" class="message"></div>
            </div>
        </div>
    </div>
</div>

<a
    class="gee-footer-logo"
    href="http://www.genuineemotionengine.com"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Visit Genuine Emotion Engine"
>
    <img alt="Genuine Emotion Engine" src="/assets/gee-header.png">
</a>

<div id="sheetBackdrop" class="bottom-sheet-backdrop"></div>

<div id="moreSheet" class="bottom-sheet" aria-hidden="true">
    <div class="bottom-sheet-panel">
        <div class="sheet-handle"></div>

        <div class="sheet-header">
            <div class="sheet-title">Player options</div>
            <button type="button" id="closeSheetButton" class="sheet-close" title="Close">×</button>
        </div>

        <div class="sheet-grid">
            <div class="sheet-card">
                <h3>Renderer</h3>
                <select id="rendererSelect"></select>
            </div>

            <div class="sheet-card">
                <h3>Stream</h3>
                <select id="streamSelect">
                    <option value="safe">Safe</option>
                    <option value="hires">Hires</option>
                </select>
            </div>

            <div class="sheet-card">
                <h3>Playback</h3>
                <div class="sheet-actions">
                    <button type="button" class="sheet-action" id="sheetLoadMusic">
                        <span class="label">Load Music</span>
                        <span class="sub">Build playlist</span>
                    </button>

                    <button type="button" class="sheet-action" id="sheetRestartTrack">
                        <span class="label">Restart Track</span>
                        <span class="sub">Play from start</span>
                    </button>
                </div>
            </div>

            <div class="sheet-card">
                <h3>Status</h3>
                <div id="sheetStatusSummary" class="meta-tertiary sheet-status-summary">
                    Waiting for player state…
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const DEFAULT_COVER = '/img/black.jpg';
const POLL_INTERVAL_MS = 5000;
const VOLUME_STEP = 5;

let rendererList = [];
let isUpdatingSelectors = false;
let isFetchingMeta = false;
let activePollHandle = null;

let uiState = {
    rendererDisplay: '',
    streamKey: 'safe',
    status: 'stop',
    title: '',
    artist: '',
    album: '',
    volume: 0
};

function fmt(sec) {
    sec = parseInt(sec || 0, 10);
    const minutes = Math.floor(sec / 60);
    const seconds = sec % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function setMessage(text = '') {
    document.getElementById('message').textContent = text;
}

function setIdleState(rendererName = '', streamName = '') {
    document.getElementById('renderer').textContent = rendererName || 'No renderer';
    document.getElementById('stream').textContent = streamName ? String(streamName).toUpperCase() : '--';
    document.getElementById('title').textContent = 'Nothing playing';
    document.getElementById('artist').textContent = '';
    document.getElementById('album').textContent = '';
    document.getElementById('elapsed').textContent = '0:00';
    document.getElementById('duration').textContent = '0:00';
    document.getElementById('progressFill').style.width = '0%';
    document.querySelector('.track-bar').setAttribute('aria-valuenow', '0');
    document.getElementById('cover').src = DEFAULT_COVER;
    document.getElementById('player').classList.add('idle', 'state-stop');
    document.getElementById('player').classList.remove('state-play', 'state-pause');
    updateVolumeUI(0);
    updateSheetSummary();
}

function applyPlaybackState(state) {
    const player = document.getElementById('player');

    player.classList.remove('state-play', 'state-pause', 'state-stop');

    if (state === 'play') {
        player.classList.remove('idle');
        player.classList.add('state-play');
        return;
    }

    if (state === 'pause') {
        player.classList.remove('idle');
        player.classList.add('state-pause');
        return;
    }

    player.classList.add('idle', 'state-stop');
}

function updateVolumeUI(volume) {
    const safeVolume = Math.max(0, Math.min(100, parseInt(volume ?? 0, 10) || 0));
    uiState.volume = safeVolume;
    document.getElementById('volumeFill').style.width = `${safeVolume}%`;
    document.getElementById('volumeBar').setAttribute('aria-valuenow', String(safeVolume));
}

function updateSheetSummary() {
    const summary = document.getElementById('sheetStatusSummary');
    const renderer = uiState.rendererDisplay || 'No renderer selected';
    const stream = uiState.streamKey ? String(uiState.streamKey).toUpperCase() : '--';
    const status = uiState.status ? String(uiState.status).toUpperCase() : 'STOP';
    const title = uiState.title || 'Nothing playing';

    summary.innerHTML = `
        <div><strong>${escapeHtml(renderer)}</strong></div>
        <div style="margin-top:6px;">${escapeHtml(stream)} · ${escapeHtml(status)}</div>
        <div style="margin-top:8px; color:#8e8e93;">${escapeHtml(title)}</div>
    `;
}

async function safeJson(fetchPromise) {
    const res = await fetchPromise;
    const text = await res.text();

    try {
        return JSON.parse(text);
    } catch (err) {
        throw new Error(`Invalid JSON response: ${text.slice(0, 200)}`);
    }
}

async function fetchRendererList() {
    try {
        const data = await safeJson(fetch('/api/?service=20', { cache: 'no-store' }));

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
    select.value = streamKey === 'hires' ? 'hires' : 'safe';
    isUpdatingSelectors = false;
}

async function selectRenderer(rendererId) {
    if (!rendererId) {
        return;
    }

    try {
        setMessage('Switching renderer…');
        const data = await safeJson(fetch(`/api/?service=21&renderer_id=${encodeURIComponent(rendererId)}`, { cache: 'no-store' }));

        if (!data || data.status !== 'ok') {
            console.error('selectRenderer failed', data);
            setMessage('Renderer switch failed');
            return;
        }

        setMessage(`Renderer selected: ${data.renderer?.display_name || rendererId}`);
        await fetchRendererList();
        await fetchMeta(true);
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
        setMessage('Switching stream…');
        const data = await safeJson(fetch(`/api/?service=23&stream=${encodeURIComponent(streamKey)}`, { cache: 'no-store' }));

        if (!data || data.status !== 'ok') {
            console.error('selectStream failed', data);
            setMessage('Stream switch failed');
            return;
        }

        setMessage(`Stream selected: ${String(streamKey).toUpperCase()}`);
        await fetchRendererList();
        await fetchMeta(true);
    } catch (err) {
        console.error('selectStream failed', err);
        setMessage('Stream switch failed');
    }
}

async function fetchMeta(force = false) {
    if (isFetchingMeta && !force) {
        return;
    }

    isFetchingMeta = true;

    try {
        const data = await safeJson(fetch('/api/?service=1', { cache: 'no-store' }));
        updateUI(data);
    } catch (err) {
        console.error('fetchMeta failed', err);
        setMessage('Unable to reach Gee Core');
    } finally {
        isFetchingMeta = false;
    }
}

function updateUI(data) {
    if (!data || data.status !== 'ok') {
        return;
    }

    const rendererDisplay = data.renderer_display || data.renderer_name || data.renderer_id || '';
    const streamKey = data.stream_key || '';

    uiState.rendererDisplay = rendererDisplay;
    uiState.streamKey = streamKey || 'safe';
    uiState.status = data.state || 'stop';
    uiState.title = data.title || '';
    uiState.artist = data.artist || '';
    uiState.album = data.album || '';

    document.getElementById('renderer').textContent = rendererDisplay || 'No renderer';
    document.getElementById('stream').textContent = streamKey ? String(streamKey).toUpperCase() : '--';

    const title = data.title || '';
    const artist = data.artist || '';
    const album = data.album || '';
    const state = data.state || 'stop';

    if (!title && !artist && !album && state === 'stop') {
        setIdleState(rendererDisplay, streamKey);
    } else {
        document.getElementById('title').textContent = title || 'Unknown Title';
        document.getElementById('artist').textContent = artist || '';
        document.getElementById('album').textContent = album || '';
    }

    if (data.image) {
        document.getElementById('cover').src = data.image;
    } else if (!title && !artist && !album) {
        document.getElementById('cover').src = DEFAULT_COVER;
    }

    const elapsed = parseInt(data.elapsed || 0, 10);
    const duration = parseInt(data.duration || 0, 10);
    const progress = duration > 0 ? Math.max(0, Math.min(100, Math.round((elapsed / duration) * 100))) : 0;

    document.getElementById('elapsed').textContent = fmt(elapsed);
    document.getElementById('duration').textContent = fmt(duration);
    document.getElementById('progressFill').style.width = `${progress}%`;
    document.querySelector('.track-bar').setAttribute('aria-valuenow', String(progress));

    updateVolumeUI(data.volume ?? 0);
    applyPlaybackState(state);
    updateSheetSummary();
}

async function sendCommand(service, successMessage = '') {
    try {
        const data = await safeJson(fetch(`/api/?service=${service}`, { cache: 'no-store' }));

        if (!data || data.status !== 'ok') {
            console.error('sendCommand failed', service, data);
            setMessage('Playback action failed');
            return;
        }

        setMessage(successMessage || '');
        await fetchMeta(true);
    } catch (err) {
        console.error('sendCommand failed', err);
        setMessage('Playback action failed');
    }
}

async function changeVolume(delta) {
    try {
        const data = await safeJson(fetch(`/api/?service=15&mod=${encodeURIComponent(delta)}`, {
            cache: 'no-store'
        }));

        if (!data || data.status !== 'ok') {
            console.error('changeVolume failed', data);
            setMessage('Volume change failed');
            return;
        }

        updateVolumeUI(data.volume ?? uiState.volume);
        setMessage('');
        await fetchMeta(true);
    } catch (err) {
        console.error('changeVolume failed', err);
        setMessage('Volume change failed');
    }
}

async function loadMusic() {
    try {
        setMessage('Loading music…');
        const data = await safeJson(fetch('/api/?service=5', { cache: 'no-store' }));

        if (!data || data.status !== 'ok') {
            console.error('loadMusic failed', data);
            setMessage('Load Music failed');
            return;
        }

        setMessage(`Music loaded${data.track_count ? ` · ${data.track_count} tracks` : ''}`);
        await fetchMeta(true);
    } catch (err) {
        console.error('loadMusic failed', err);
        setMessage('Load Music failed');
    }
}

function openMoreSheet() {
    document.getElementById('moreSheet').classList.add('open');
    document.getElementById('sheetBackdrop').classList.add('open');
    document.getElementById('moreSheet').setAttribute('aria-hidden', 'false');
}

function closeMoreSheet() {
    document.getElementById('moreSheet').classList.remove('open');
    document.getElementById('sheetBackdrop').classList.remove('open');
    document.getElementById('moreSheet').setAttribute('aria-hidden', 'true');
}

function bindEvents() {
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

    document.getElementById('closeSheetButton').addEventListener('click', closeMoreSheet);
    document.getElementById('sheetBackdrop').addEventListener('click', closeMoreSheet);

    document.getElementById('volDownButton').addEventListener('click', async () => {
        await changeVolume(-VOLUME_STEP);
    });

    document.getElementById('volUpButton').addEventListener('click', async () => {
        await changeVolume(VOLUME_STEP);
    });

    document.getElementById('sheetLoadMusic').addEventListener('click', async () => {
        closeMoreSheet();
        await loadMusic();
    });

    document.getElementById('sheetRestartTrack').addEventListener('click', async () => {
        closeMoreSheet();
        await sendCommand(13);
    });

    document.querySelectorAll('.zone').forEach((zone) => {
        zone.addEventListener('click', async () => {
            const action = zone.dataset.zone || '';

            switch (action) {
                case 'refresh':
                    await fetchMeta(true);
                    break;
                case 'more':
                    openMoreSheet();
                    break;
                case 'load':
                    await loadMusic();
                    break;
                case 'playpause':
                    await sendCommand(2);
                    break;
                case 'prev':
                    await sendCommand(3);
                    break;
                case 'restart':
                    await sendCommand(13);
                    break;
                case 'next':
                    await sendCommand(4);
                    break;
                default:
                    break;
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMoreSheet();
        }
    });
}

async function initialisePlayer() {
    bindEvents();
    await fetchRendererList();
    await fetchMeta(true);
    setMessage('');
    activePollHandle = window.setInterval(fetchMeta, POLL_INTERVAL_MS);
}

initialisePlayer();
</script>
</body>
</html>