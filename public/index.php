<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#000000">
<link rel="icon" href="/favicon.ico">
<style>
:root{
    --bg:#000;
    --panel:#0a0a0a;
    --panel-2:#111;
    --border:#1f1f1f;
    --border-strong:#2f2f2f;
    --text:#fff;
    --muted:#8e8e93;
    --muted-2:#6b6b70;
    --accent:#fff;
    --shadow:0 18px 60px rgba(0,0,0,.45);
    --radius:24px;
    --sheet-radius:28px;
    --player-width:min(92vw, 520px);
    --art-size:min(92vw, 520px);
    --transition:180ms ease;
}
*{box-sizing:border-box}
html,body{height:100%}
body{
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display","SF Pro Text","Helvetica Neue",Arial,sans-serif;
    -webkit-font-smoothing:antialiased;
    text-rendering:optimizeLegibility;
    overflow:hidden;
    user-select:none;
}
button,input,select{font:inherit}
button{
    background:none;
    color:inherit;
    border:0;
    margin:0;
    padding:0;
}
select{
    appearance:none;
    -webkit-appearance:none;
}
.hidden{
    display:none !important;
}
#app{
    min-height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px 16px calc(28px + env(safe-area-inset-bottom));
}
.player-shell{
    width:100%;
    max-width:760px;
}
.player{
    width:var(--player-width);
    margin:0 auto;
    display:flex;
    flex-direction:column;
    gap:18px;
}
.player.idle .meta-primary,
.player.idle .meta-secondary{
    opacity:.75;
}
.hero{
    position:relative;
}
.art-frame{
    position:relative;
    width:100%;
    aspect-ratio:1 / 1;
    border-radius:var(--radius);
    overflow:hidden;
    background:#0b0b0b;
    box-shadow:var(--shadow);
}
.art-frame::after{
    content:"";
    position:absolute;
    inset:0;
    background:
        linear-gradient(to bottom, rgba(0,0,0,.08), rgba(0,0,0,.18)),
        radial-gradient(circle at 50% 50%, rgba(255,255,255,.03), transparent 55%);
    pointer-events:none;
}
#cover{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    background:#0f0f0f;
}
.art-grid{
    position:absolute;
    inset:0;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    grid-template-rows:repeat(3,1fr);
    z-index:2;
}
.zone{
    position:relative;
    cursor:pointer;
    background:transparent;
    transition:background var(--transition);
    -webkit-tap-highlight-color:transparent;
}
.zone:active{
    background:rgba(255,255,255,.08);
}
.zone.disabled{
    cursor:default;
}
.zone.disabled:active{
    background:transparent;
}
.status-chip{
    position:absolute;
    left:14px;
    top:14px;
    z-index:3;
    display:flex;
    gap:8px;
    align-items:center;
    padding:8px 12px;
    border:1px solid rgba(255,255,255,.08);
    border-radius:999px;
    background:rgba(0,0,0,.45);
    backdrop-filter:blur(12px);
}
.status-dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:#666;
    transition:background var(--transition), box-shadow var(--transition);
}
.player.state-play .status-dot{
    background:#fff;
    box-shadow:0 0 14px rgba(255,255,255,.55);
}
.player.state-pause .status-dot{
    background:#9f9f9f;
}
.status-chip-text{
    font-size:.72rem;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:#f3f3f3;
}
.center-glyph{
    position:absolute;
    inset:50% auto auto 50%;
    transform:translate(-50%,-50%);
    z-index:3;
    width:112px;
    height:112px;
    border-radius:999px;
    background:rgba(0,0,0,.34);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(10px);
    display:flex;
    align-items:center;
    justify-content:center;
    pointer-events:none;
    opacity:1;
    transition:opacity var(--transition), transform var(--transition);
}
.player.state-stop .center-glyph,
.player.idle .center-glyph{
    opacity:.92;
}
.glyph-play,
.glyph-pause{
    position:relative;
    width:36px;
    height:36px;
}
.glyph-play::before{
    content:"";
    position:absolute;
    left:10px;
    top:4px;
    border-style:solid;
    border-width:14px 0 14px 23px;
    border-color:transparent transparent transparent #fff;
}
.glyph-pause::before,
.glyph-pause::after{
    content:"";
    position:absolute;
    top:4px;
    width:9px;
    height:28px;
    background:#fff;
    border-radius:2px;
}
.glyph-pause::before{left:7px}
.glyph-pause::after{right:7px}
.player.state-play .glyph-play{display:none}
.player.state-pause .glyph-pause,
.player.state-stop .glyph-pause,
.player.idle .glyph-pause{display:none}

.transport-hints{
    position:absolute;
    inset:auto 14px 14px 14px;
    z-index:3;
    display:flex;
    justify-content:space-between;
    pointer-events:none;
}
.transport-pill{
    display:flex;
    align-items:center;
    justify-content:center;
    min-width:58px;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(0,0,0,.42);
    border:1px solid rgba(255,255,255,.08);
    color:#f4f4f5;
    font-size:.7rem;
    letter-spacing:.12em;
    text-transform:uppercase;
    backdrop-filter:blur(12px);
}

.topline{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.device-stack{
    min-width:0;
}
.device-name{
    font-size:.82rem;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#f3f3f3;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.stream-name{
    margin-top:4px;
    font-size:.76rem;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:var(--muted);
}
.top-actions{
    display:flex;
    gap:8px;
    flex-shrink:0;
}
.ghost-button{
    min-width:42px;
    height:42px;
    padding:0 12px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:999px;
    border:1px solid var(--border-strong);
    background:rgba(255,255,255,.02);
    color:#f5f5f5;
    cursor:pointer;
    transition:background var(--transition), border-color var(--transition), transform var(--transition);
}
.ghost-button:hover{
    background:rgba(255,255,255,.05);
}
.ghost-button:active{
    transform:scale(.98);
}
.ghost-button .mini{
    font-size:.72rem;
    letter-spacing:.12em;
    text-transform:uppercase;
}
.ghost-button.icon{
    width:42px;
    padding:0;
    font-size:1.08rem;
}

.meta{
    display:flex;
    flex-direction:column;
    gap:6px;
}
.meta-primary{
    font-size:clamp(2rem, 5vw, 2.8rem);
    line-height:1;
    letter-spacing:-.03em;
    min-height:2.8rem;
}
.meta-secondary{
    font-size:clamp(1.05rem, 3vw, 1.3rem);
    color:#efefef;
    min-height:1.4rem;
}
.meta-tertiary{
    font-size:.98rem;
    color:var(--muted);
    min-height:1.2rem;
}

.progress-wrap,
.volume-wrap{
    display:flex;
    flex-direction:column;
    gap:8px;
}
.time-row,
.volume-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    font-size:.78rem;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--muted);
}
.track-bar,
.volume-bar{
    width:100%;
    height:4px;
    border-radius:999px;
    overflow:hidden;
    background:#1a1a1a;
}
.track-fill,
.volume-fill{
    height:100%;
    width:0%;
    border-radius:999px;
    background:#fff;
    transition:width 220ms linear;
}
.controls-row{
    display:grid;
    grid-template-columns:auto 1fr auto;
    align-items:center;
    gap:14px;
}
.transport-row{
    display:flex;
    justify-content:center;
    gap:14px;
}
.transport-button{
    width:64px;
    height:64px;
    border-radius:999px;
    border:1px solid var(--border-strong);
    background:#0f0f10;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:background var(--transition), transform var(--transition), border-color var(--transition);
}
.transport-button.large{
    width:78px;
    height:78px;
}
.transport-button:hover{
    background:#161617;
}
.transport-button:active{
    transform:scale(.98);
}
.transport-icon{
    font-size:1.2rem;
    color:#fff;
}
.transport-button.large .transport-icon{
    font-size:1.45rem;
}
.volume-buttons{
    display:flex;
    justify-content:center;
    gap:10px;
}
.volume-button{
    min-width:48px;
    height:48px;
    border-radius:999px;
    border:1px solid var(--border-strong);
    background:#0f0f10;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:background var(--transition), transform var(--transition);
}
.volume-button:hover{background:#161617}
.volume-button:active{transform:scale(.98)}
.message{
    min-height:1.15rem;
    font-size:.82rem;
    color:var(--muted);
}
.bottom-sheet-backdrop{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.58);
    backdrop-filter:blur(4px);
    z-index:30;
    opacity:0;
    pointer-events:none;
    transition:opacity var(--transition);
}
.bottom-sheet-backdrop.open{
    opacity:1;
    pointer-events:auto;
}
.bottom-sheet{
    position:fixed;
    left:0;
    right:0;
    bottom:0;
    z-index:31;
    transform:translateY(104%);
    transition:transform 240ms ease;
    padding:0 12px calc(12px + env(safe-area-inset-bottom));
}
.bottom-sheet.open{
    transform:translateY(0);
}
.bottom-sheet-panel{
    max-width:760px;
    margin:0 auto;
    border-radius:var(--sheet-radius) var(--sheet-radius) 0 0;
    background:rgba(10,10,10,.96);
    border:1px solid var(--border);
    border-bottom:0;
    box-shadow:0 -24px 70px rgba(0,0,0,.45);
    padding:14px 16px 22px;
}
.sheet-handle{
    width:42px;
    height:5px;
    border-radius:999px;
    background:#36363a;
    margin:4px auto 16px;
}
.sheet-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:18px;
}
.sheet-title{
    font-size:.8rem;
    letter-spacing:.2em;
    text-transform:uppercase;
    color:var(--muted);
}
.sheet-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}
.sheet-card{
    background:#0f0f10;
    border:1px solid var(--border);
    border-radius:18px;
    padding:14px;
}
.sheet-card h3{
    margin:0 0 10px;
    font-size:.76rem;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:var(--muted);
    font-weight:600;
}
.sheet-card select{
    width:100%;
    border:1px solid var(--border-strong);
    background:#080808;
    color:#fff;
    padding:12px 14px;
    border-radius:14px;
    outline:none;
}
.sheet-actions{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:10px;
}
.sheet-action{
    min-height:52px;
    padding:14px;
    border-radius:16px;
    border:1px solid var(--border-strong);
    background:#080808;
    color:#fff;
    text-align:left;
    cursor:pointer;
    transition:background var(--transition), transform var(--transition);
}
.sheet-action:hover{background:#111}
.sheet-action:active{transform:scale(.99)}
.sheet-action .label{
    display:block;
    font-size:.86rem;
}
.sheet-action .sub{
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:.74rem;
    letter-spacing:.08em;
    text-transform:uppercase;
}
@media (max-width: 700px){
    #app{
        padding-top:16px;
        align-items:flex-start;
    }
    .player{
        width:100%;
    }
    .sheet-grid{
        grid-template-columns:1fr;
    }
    .controls-row{
        grid-template-columns:1fr;
    }
    .volume-buttons{
        order:2;
    }
    .transport-row{
        order:1;
    }
}
@media (max-width: 460px){
    .meta-primary{
        font-size:1.7rem;
        min-height:2.1rem;
    }
    .meta-secondary{font-size:1rem}
    .transport-button{width:58px;height:58px}
    .transport-button.large{width:72px;height:72px}
    .center-glyph{
        width:92px;
        height:92px;
    }
    .ghost-button.icon,
    .ghost-button{
        min-width:40px;
        height:40px;
    }
}
</style>
</head>
<body>
<div id="app">
    <div class="player-shell">
        <div id="player" class="player idle state-stop">
            <div class="hero">
                <div class="art-frame">
                    <img id="cover" src="/img/black.jpg" alt="Artwork">
                    <div class="status-chip">
                        <span class="status-dot"></span>
                        <span id="statusChipText" class="status-chip-text">Stopped</span>
                    </div>

                    <div class="center-glyph" aria-hidden="true">
                        <span class="glyph-play"></span>
                        <span class="glyph-pause"></span>
                    </div>

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

                    <div class="transport-hints" aria-hidden="true">
                        <span class="transport-pill">Prev</span>
                        <span class="transport-pill">Next</span>
                    </div>
                </div>
            </div>

            <div class="topline">
                <div class="device-stack">
                    <div id="renderer" class="device-name">Loading…</div>
                    <div id="stream" class="stream-name">Stream: --</div>
                </div>
                <div class="top-actions">
                    <button type="button" id="refreshButton" class="ghost-button icon" title="Refresh">↻</button>
                    <button type="button" id="moreButton" class="ghost-button" title="More">
                        <span class="mini">More</span>
                    </button>
                </div>
            </div>

            <div class="meta">
                <div id="title" class="meta-primary">Loading…</div>
                <div id="artist" class="meta-secondary"></div>
                <div id="album" class="meta-tertiary"></div>
            </div>

            <div class="progress-wrap">
                <div class="time-row">
                    <span id="elapsed">0:00</span>
                    <span id="status">Connecting…</span>
                    <span id="duration">0:00</span>
                </div>
                <div class="track-bar" role="progressbar" aria-label="Track progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div id="progressFill" class="track-fill"></div>
                </div>
            </div>

            <div class="controls-row">
                <div class="volume-buttons">
                    <button type="button" class="volume-button" id="volDownButton" title="Volume down">−</button>
                    <button type="button" class="volume-button" id="volUpButton" title="Volume up">+</button>
                </div>

                <div class="transport-row">
                    <button type="button" class="transport-button" id="prevButton" title="Previous">
                        <span class="transport-icon">⏮</span>
                    </button>
                    <button type="button" class="transport-button large" id="playPauseButton" title="Play / Pause">
                        <span id="playPauseLabel" class="transport-icon">▶</span>
                    </button>
                    <button type="button" class="transport-button" id="nextButton" title="Next">
                        <span class="transport-icon">⏭</span>
                    </button>
                </div>

                <div class="volume-wrap">
                    <div class="volume-row">
                        <span>Volume</span>
                        <span id="volume">0</span>
                    </div>
                    <div class="volume-bar" role="progressbar" aria-label="Volume" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div id="volumeFill" class="volume-fill"></div>
                    </div>
                </div>
            </div>

            <div id="message" class="message"></div>
        </div>
    </div>
</div>

<div id="sheetBackdrop" class="bottom-sheet-backdrop"></div>
<div id="moreSheet" class="bottom-sheet" aria-hidden="true">
    <div class="bottom-sheet-panel">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div class="sheet-title">Player options</div>
            <button type="button" id="closeSheetButton" class="ghost-button icon" title="Close">×</button>
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
                <div id="sheetStatusSummary" class="meta-tertiary" style="min-height:auto;">
                    Waiting for player state…
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const DEFAULT_COVER = '/img/black.jpg';
const POLL_INTERVAL_MS = 5000;

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
    document.getElementById('stream').textContent = streamName ? `Stream: ${String(streamName).toUpperCase()}` : 'Stream: --';
    document.getElementById('status').textContent = 'Stopped';
    document.getElementById('statusChipText').textContent = 'Stopped';
    document.getElementById('title').textContent = 'Nothing playing';
    document.getElementById('artist').textContent = '';
    document.getElementById('album').textContent = '';
    document.getElementById('elapsed').textContent = '0:00';
    document.getElementById('duration').textContent = '0:00';
    document.getElementById('progressFill').style.width = '0%';
    document.querySelector('.track-bar').setAttribute('aria-valuenow', '0');
    document.getElementById('cover').src = DEFAULT_COVER;
    document.getElementById('playPauseLabel').textContent = '▶';
    document.getElementById('player').classList.add('idle', 'state-stop');
    document.getElementById('player').classList.remove('state-play', 'state-pause');
    updateSheetSummary();
}

function applyPlaybackState(state) {
    const player = document.getElementById('player');
    const status = document.getElementById('status');
    const chip = document.getElementById('statusChipText');
    const playPauseLabel = document.getElementById('playPauseLabel');

    player.classList.remove('state-play', 'state-pause', 'state-stop');

    if (state === 'play') {
        playPauseLabel.textContent = '⏸';
        status.textContent = 'Playing';
        chip.textContent = 'Playing';
        player.classList.remove('idle');
        player.classList.add('state-play');
        return;
    }

    if (state === 'pause') {
        playPauseLabel.textContent = '▶';
        status.textContent = 'Paused';
        chip.textContent = 'Paused';
        player.classList.remove('idle');
        player.classList.add('state-pause');
        return;
    }

    playPauseLabel.textContent = '▶';
    status.textContent = 'Stopped';
    chip.textContent = 'Stopped';
    player.classList.add('idle', 'state-stop');
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
        document.getElementById('status').textContent = 'Connection error';
        document.getElementById('statusChipText').textContent = 'Offline';
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
    const streamDisplay = streamKey ? `Stream: ${String(streamKey).toUpperCase()}` : 'Stream: --';

    uiState.rendererDisplay = rendererDisplay;
    uiState.streamKey = streamKey || 'safe';
    uiState.status = data.state || 'stop';
    uiState.title = data.title || '';
    uiState.artist = data.artist || '';
    uiState.album = data.album || '';
    uiState.volume = parseInt(data.volume ?? 0, 10) || 0;

    document.getElementById('renderer').textContent = rendererDisplay || 'No renderer';
    document.getElementById('stream').textContent = streamDisplay;

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

    const volume = Math.max(0, Math.min(100, parseInt(data.volume ?? 0, 10) || 0));
    document.getElementById('volume').textContent = String(volume);
    document.getElementById('volumeFill').style.width = `${volume}%`;
    document.querySelector('.volume-bar').setAttribute('aria-valuenow', String(volume));

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

        if (successMessage) {
            setMessage(successMessage);
        } else {
            setMessage('');
        }

        await fetchMeta(true);
    } catch (err) {
        console.error('sendCommand failed', err);
        setMessage('Playback action failed');
    }
}

async function changeVolume(delta) {
    try {
        const data = await safeJson(fetch(`/api/?service=15&mod=${encodeURIComponent(delta)}`, { cache: 'no-store' }));

        if (!data || data.status !== 'ok') {
            console.error('changeVolume failed', data);
            setMessage('Volume change failed');
            return;
        }

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

    document.getElementById('refreshButton').addEventListener('click', () => fetchMeta(true));
    document.getElementById('moreButton').addEventListener('click', openMoreSheet);
    document.getElementById('closeSheetButton').addEventListener('click', closeMoreSheet);
    document.getElementById('sheetBackdrop').addEventListener('click', closeMoreSheet);

    document.getElementById('prevButton').addEventListener('click', () => sendCommand(3));
    document.getElementById('nextButton').addEventListener('click', () => sendCommand(4));
    document.getElementById('playPauseButton').addEventListener('click', () => sendCommand(2));
    document.getElementById('volDownButton').addEventListener('click', () => changeVolume(-5));
    document.getElementById('volUpButton').addEventListener('click', () => changeVolume(5));

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
