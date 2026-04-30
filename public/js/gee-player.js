const GeePlayer = (() => {
    'use strict';

    const DEFAULT_COVER = '/img/black.jpg';
    const META_POLL_INTERVAL_MS = 15000;
    const PROGRESS_TICK_INTERVAL_MS = 1000;
    const GRID_HELPER_DURATION_MS = 5000;
    const VOLUME_STEP = 5;

    const state = {
        rendererList: [],
        isUpdatingSelectors: false,
        isFetchingMeta: false,
        metaPollHandle: null,
        progressTickHandle: null,
        gridHelperTimeoutHandle: null,
        gridHelperVisible: false,

        ui: {
            rendererDisplay: '',
            streamKey: 'safe',
            status: 'stop',
            title: '',
            artist: '',
            album: '',
            volume: 0
        },

        playbackClock: {
            elapsed: 0,
            duration: 0,
            state: 'stop'
        }
    };

    const els = {};

    function cacheElements() {
        els.player = document.getElementById('player');
        els.cover = document.getElementById('cover');

        els.renderer = document.getElementById('renderer');
        els.stream = document.getElementById('stream');

        els.title = document.getElementById('title');
        els.artist = document.getElementById('artist');
        els.album = document.getElementById('album');

        els.elapsed = document.getElementById('elapsed');
        els.duration = document.getElementById('duration');

        els.progressFill = document.getElementById('progressFill');
        els.trackBar = document.querySelector('.track-bar');

        els.volumeFill = document.getElementById('volumeFill');
        els.volumeBar = document.getElementById('volumeBar');

        els.message = document.getElementById('message');

        els.rendererSelect = document.getElementById('rendererSelect');
        els.streamSelect = document.getElementById('streamSelect');

        els.volDownButton = document.getElementById('volDownButton');
        els.volUpButton = document.getElementById('volUpButton');

        els.sheetLoadMusic = document.getElementById('sheetLoadMusic');
        els.sheetRestartTrack = document.getElementById('sheetRestartTrack');

        els.moreSheet = document.getElementById('moreSheet');
        els.sheetBackdrop = document.getElementById('sheetBackdrop');
        els.closeSheetButton = document.getElementById('closeSheetButton');
        els.sheetStatusSummary = document.getElementById('sheetStatusSummary');

        els.gridHelper = document.getElementById('gridHelper');
        els.gridHelperToggle = document.getElementById('gridHelperToggle');
        
        els.featureModal = document.getElementById('featureModal');
        els.featureModalBackdrop = document.getElementById('featureModalBackdrop');
        els.featureModalClose = document.getElementById('featureModalClose');
        els.featureModalTitle = document.getElementById('featureModalTitle');
        els.featureModalBody = document.getElementById('featureModalBody');     

        els.zones = document.querySelectorAll('.zone');
    }

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
        els.message.textContent = text;
    }

    function renderProgress(elapsed, duration) {
        const safeElapsed = Math.max(0, parseInt(elapsed || 0, 10));
        const safeDuration = Math.max(0, parseInt(duration || 0, 10));
        const progress = safeDuration > 0
            ? Math.max(0, Math.min(100, Math.round((safeElapsed / safeDuration) * 100)))
            : 0;

        els.elapsed.textContent = fmt(safeElapsed);
        els.duration.textContent = fmt(safeDuration);
        els.progressFill.style.width = `${progress}%`;
        els.trackBar.setAttribute('aria-valuenow', String(progress));
    }

    function syncPlaybackClock(elapsed, duration, playbackState) {
        state.playbackClock.elapsed = Math.max(0, parseInt(elapsed || 0, 10));
        state.playbackClock.duration = Math.max(0, parseInt(duration || 0, 10));
        state.playbackClock.state = String(playbackState || 'stop');
        renderProgress(state.playbackClock.elapsed, state.playbackClock.duration);
    }

    function startProgressTicker() {
        if (state.progressTickHandle !== null) {
            window.clearInterval(state.progressTickHandle);
        }

        state.progressTickHandle = window.setInterval(async () => {
            if (state.playbackClock.state !== 'play') {
                return;
            }

            if (state.playbackClock.duration <= 0) {
                return;
            }

            state.playbackClock.elapsed += 1;
            renderProgress(state.playbackClock.elapsed, state.playbackClock.duration);

            if (state.playbackClock.elapsed >= state.playbackClock.duration) {
                await fetchMeta(true);
            }
        }, PROGRESS_TICK_INTERVAL_MS);
    }

    function updateVolumeUI(volume) {
        const safeVolume = Math.max(0, Math.min(100, parseInt(volume ?? 0, 10) || 0));
        state.ui.volume = safeVolume;
        els.volumeFill.style.width = `${safeVolume}%`;
        els.volumeBar.setAttribute('aria-valuenow', String(safeVolume));
    }

    function applyPlaybackState(playbackState) {
        els.player.classList.remove('state-play', 'state-pause', 'state-stop');

        if (playbackState === 'play') {
            els.player.classList.remove('idle');
            els.player.classList.add('state-play');
            return;
        }

        if (playbackState === 'pause') {
            els.player.classList.remove('idle');
            els.player.classList.add('state-pause');
            return;
        }

        els.player.classList.add('idle', 'state-stop');
    }

    function updateSheetSummary() {
        const renderer = state.ui.rendererDisplay || 'No renderer selected';
        const stream = state.ui.streamKey ? String(state.ui.streamKey).toUpperCase() : '--';
        const playbackState = state.ui.status ? String(state.ui.status).toUpperCase() : 'STOP';
        const title = state.ui.title || 'Nothing playing';

        els.sheetStatusSummary.innerHTML = `
            <div><strong>${escapeHtml(renderer)}</strong></div>
            <div style="margin-top:6px;">${escapeHtml(stream)} · ${escapeHtml(playbackState)}</div>
            <div style="margin-top:8px; color:#8e8e93;">${escapeHtml(title)}</div>
        `;
    }

    function setGridHelperVisible(visible) {
        state.gridHelperVisible = visible;
        els.player.classList.toggle('grid-helper-visible', visible);
        els.gridHelper.setAttribute('aria-hidden', visible ? 'false' : 'true');
        els.gridHelperToggle.setAttribute('aria-label', visible ? 'Hide navigation grid' : 'Show navigation grid');
        els.gridHelperToggle.setAttribute('title', visible ? 'Hide navigation grid' : 'Show navigation grid');
    }

    function hideGridHelper() {
        if (state.gridHelperTimeoutHandle !== null) {
            window.clearTimeout(state.gridHelperTimeoutHandle);
            state.gridHelperTimeoutHandle = null;
        }

        setGridHelperVisible(false);
    }

    function showGridHelper() {
        if (state.gridHelperTimeoutHandle !== null) {
            window.clearTimeout(state.gridHelperTimeoutHandle);
            state.gridHelperTimeoutHandle = null;
        }

        setGridHelperVisible(true);

        state.gridHelperTimeoutHandle = window.setTimeout(() => {
            hideGridHelper();
        }, GRID_HELPER_DURATION_MS);
    }

    function toggleGridHelper() {
        if (state.gridHelperVisible) {
            hideGridHelper();
            return;
        }

        showGridHelper();
    }

    function openFeatureModal(title = '') {
        if (!els.featureModal || !els.featureModalBackdrop) {
            return;
        }

        els.featureModalTitle.textContent = title;
        els.featureModalBody.innerHTML = '';

        els.featureModal.classList.add('open');
        els.featureModalBackdrop.classList.add('open');
        els.featureModal.setAttribute('aria-hidden', 'false');

        hideGridHelper();
    }

    function closeFeatureModal() {
        if (!els.featureModal || !els.featureModalBackdrop) {
            return;
        }

        els.featureModal.classList.remove('open');
        els.featureModalBackdrop.classList.remove('open');
        els.featureModal.setAttribute('aria-hidden', 'true');
    }

    function setIdleState(rendererName = '', streamName = '') {
        els.renderer.textContent = rendererName || 'No renderer';
        els.stream.textContent = streamName ? String(streamName).toUpperCase() : '--';
        els.title.textContent = 'Nothing playing';
        els.artist.textContent = '';
        els.album.textContent = '';
        els.cover.src = DEFAULT_COVER;

        els.player.classList.add('idle', 'state-stop');
        els.player.classList.remove('state-play', 'state-pause');

        syncPlaybackClock(0, 0, 'stop');
        updateVolumeUI(0);
        updateSheetSummary();
    }

    async function safeJson(fetchPromise) {
        const response = await fetchPromise;
        const text = await response.text();

        try {
            return JSON.parse(text);
        } catch (err) {
            throw new Error(`Invalid JSON response: ${text.slice(0, 200)}`);
        }
    }

    function populateRendererSelect(selectedRendererId = '') {
        const previous = els.rendererSelect.value;

        state.isUpdatingSelectors = true;
        els.rendererSelect.innerHTML = '';

        if (!state.rendererList.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No renderers';
            els.rendererSelect.appendChild(option);
            state.isUpdatingSelectors = false;
            return;
        }

        state.rendererList.forEach((renderer) => {
            const option = document.createElement('option');
            option.value = renderer.renderer_id || '';
            option.textContent = renderer.display_name || renderer.renderer_name || renderer.renderer_id || 'Unknown';
            els.rendererSelect.appendChild(option);
        });

        const finalValue = selectedRendererId || previous || (state.rendererList[0]?.renderer_id ?? '');
        els.rendererSelect.value = finalValue;
        state.isUpdatingSelectors = false;
    }

    function setStreamSelect(streamKey = 'safe') {
        state.isUpdatingSelectors = true;
        els.streamSelect.value = streamKey === 'hires' ? 'hires' : 'safe';
        state.isUpdatingSelectors = false;
    }

    async function fetchRendererList() {
        try {
            const data = await safeJson(fetch('/api/?service=20', { cache: 'no-store' }));

            if (!data || data.status !== 'ok' || !Array.isArray(data.renderers)) {
                return;
            }

            state.rendererList = data.renderers;
            populateRendererSelect(data.selected_renderer_id || '');
            setStreamSelect(data.selected_stream || 'safe');
        } catch (err) {
            console.error('fetchRendererList failed', err);
        }
    }

    async function selectRenderer(rendererId) {
        if (!rendererId) {
            return;
        }

        try {
            setMessage('Switching renderer…');
            const data = await safeJson(
                fetch(`/api/?service=21&renderer_id=${encodeURIComponent(rendererId)}`, { cache: 'no-store' })
            );

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
            const data = await safeJson(
                fetch(`/api/?service=23&stream=${encodeURIComponent(streamKey)}`, { cache: 'no-store' })
            );

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

    function updateUI(data) {
        if (!data || data.status !== 'ok') {
            return;
        }

        const rendererDisplay = data.renderer_display || data.renderer_name || data.renderer_id || '';
        const streamKey = data.stream_key || '';
        const title = data.title || '';
        const artist = data.artist || '';
        const album = data.album || '';
        const playbackState = data.state || 'stop';
        const elapsed = parseInt(data.elapsed || 0, 10);
        const duration = parseInt(data.duration || 0, 10);

        state.ui.rendererDisplay = rendererDisplay;
        state.ui.streamKey = streamKey || 'safe';
        state.ui.status = playbackState;
        state.ui.title = title;
        state.ui.artist = artist;
        state.ui.album = album;

        els.renderer.textContent = rendererDisplay || 'No renderer';
        els.stream.textContent = streamKey ? String(streamKey).toUpperCase() : '--';

        if (!title && !artist && !album && playbackState === 'stop') {
            setIdleState(rendererDisplay, streamKey);
        } else {
            els.title.textContent = title || 'Unknown Title';
            els.artist.textContent = artist || '';
            els.album.textContent = album || '';
            syncPlaybackClock(elapsed, duration, playbackState);
        }

        if (data.image) {
            els.cover.src = data.image;
        } else if (!title && !artist && !album) {
            els.cover.src = DEFAULT_COVER;
        }

        updateVolumeUI(data.volume ?? 0);
        applyPlaybackState(playbackState);
        updateSheetSummary();
    }

    async function fetchMeta(force = false) {
        if (state.isFetchingMeta && !force) {
            return;
        }

        state.isFetchingMeta = true;

        try {
            const data = await safeJson(fetch('/api/?service=1', { cache: 'no-store' }));
            updateUI(data);
        } catch (err) {
            console.error('fetchMeta failed', err);
            setMessage('Unable to reach Gee Core');
        } finally {
            state.isFetchingMeta = false;
        }
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
            const data = await safeJson(
                fetch(`/api/?service=15&mod=${encodeURIComponent(delta)}`, { cache: 'no-store' })
            );

            if (!data || data.status !== 'ok') {
                console.error('changeVolume failed', data);
                setMessage('Volume change failed');
                return;
            }

            updateVolumeUI(data.volume ?? state.ui.volume);
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
        els.moreSheet.classList.add('open');
        els.sheetBackdrop.classList.add('open');
        els.moreSheet.setAttribute('aria-hidden', 'false');
    }

    function closeMoreSheet() {
        els.moreSheet.classList.remove('open');
        els.sheetBackdrop.classList.remove('open');
        els.moreSheet.setAttribute('aria-hidden', 'true');
    }

    function bindEvents() {
        els.rendererSelect.addEventListener('change', async function () {
            if (state.isUpdatingSelectors) {
                return;
            }
            await selectRenderer(this.value);
        });

        els.streamSelect.addEventListener('change', async function () {
            if (state.isUpdatingSelectors) {
                return;
            }
            await selectStream(this.value);
        });

        els.closeSheetButton.addEventListener('click', closeMoreSheet);
        els.sheetBackdrop.addEventListener('click', closeMoreSheet);
        
        if (els.featureModalClose) {
            els.featureModalClose.addEventListener('click', closeFeatureModal);
        }

        if (els.featureModalBackdrop) {
            els.featureModalBackdrop.addEventListener('click', closeFeatureModal);
        }

        els.gridHelperToggle.addEventListener('click', () => {
            toggleGridHelper();
        });

        els.volDownButton.addEventListener('click', async () => {
            await changeVolume(-VOLUME_STEP);
        });

        els.volUpButton.addEventListener('click', async () => {
            await changeVolume(VOLUME_STEP);
        });

        els.sheetLoadMusic.addEventListener('click', async () => {
            closeMoreSheet();
            await loadMusic();
        });

        els.sheetRestartTrack.addEventListener('click', async () => {
            closeMoreSheet();
            await sendCommand(13);
        });

        els.zones.forEach((zone) => {
            zone.addEventListener('click', async () => {
                const action = zone.dataset.zone || '';

                switch (action) {
                    case 'refresh':
                        await fetchMeta(true);
                        break;

                    case 'search':
                        openFeatureModal('Search');
                        break;

                    case 'more':
                        openMoreSheet();
                        break;

                    case 'load':
                        await loadMusic();
                        break;

                    case 'multiroom':
                        openFeatureModal('Multi Room');
                        break;

                    case 'playpause':
                        await sendCommand(2);
                        break;

                    case 'renderers':
                        openFeatureModal('Renderers');
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
}                }
            });
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMoreSheet();
                hideGridHelper();
                closeFeatureModal();
            }
        });
    }

    async function init() {
        cacheElements();
        bindEvents();
        startProgressTicker();
        setGridHelperVisible(false);

        await fetchRendererList();
        await fetchMeta(true);
        setMessage('');

        if (state.metaPollHandle !== null) {
            window.clearInterval(state.metaPollHandle);
        }

        state.metaPollHandle = window.setInterval(() => {
            fetchMeta(false);
        }, META_POLL_INTERVAL_MS);
    }

    return {
        init
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    GeePlayer.init();
});