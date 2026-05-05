const GeePlayer = (() => {
    'use strict';

    const DEFAULT_COVER = '/img/black.jpg';
    const META_POLL_INTERVAL_MS = 15000;
    const PROGRESS_TICK_INTERVAL_MS = 1000;
    const GRID_HELPER_DURATION_MS = 5000;
    const SEARCH_DEBOUNCE_MS = 220;
    const TRACK_SEARCH_ENDPOINT = '/api/search.php';
    const VOLUME_STEP = 5;
    const TRACK_ACTION_ENDPOINT = '/api/track-action.php';
    const ALBUM_SEARCH_ENDPOINT = '/api/search-albums.php';
    const ALBUM_TRACKS_ENDPOINT = '/api/album-tracks.php';
    const ALBUM_ACTION_ENDPOINT = '/api/album-action.php';
    const ARTIST_SEARCH_ENDPOINT = '/api/search-artists.php';
    const ARTIST_ALBUMS_ENDPOINT = '/api/artist-albums.php';

    const state = {
        rendererList: [],
        isUpdatingSelectors: false,
        isFetchingMeta: false,
        metaPollHandle: null,
        progressTickHandle: null,
        gridHelperTimeoutHandle: null,
        gridHelperVisible: false,
        searchTimeoutHandle: null,

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
        els.nextTrack = document.getElementById('nextTrack');

        els.zones = document.querySelectorAll('.zone');
    }

    function openAlbumSearchPanel() {
        
        els.featureModal.classList.add('search-modal-active');
        
        els.featureModalTitle.textContent = 'Album Search';

        els.featureModalBody.innerHTML = `
            <div class="search-modal">
                <input
                    id="albumSearchInput"
                    class="search-modal-input"
                    type="search"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    placeholder=""
                >

                <div id="albumSearchResults" class="search-modal-results"></div>
            </div>
        `;

        const input = document.getElementById('albumSearchInput');
        const results = document.getElementById('albumSearchResults');

        window.setTimeout(() => {
            input.focus();
        }, 80);

        input.addEventListener('input', () => {
            const query = input.value.trim();

            if (state.searchTimeoutHandle !== null) {
                window.clearTimeout(state.searchTimeoutHandle);
                state.searchTimeoutHandle = null;
            }

            if (query.length < 2) {
                results.innerHTML = '';
                return;
            }

            state.searchTimeoutHandle = window.setTimeout(() => {
                searchAlbums(query);
            }, SEARCH_DEBOUNCE_MS);
        });
    }

    async function searchAlbums(query) {
        const results = document.getElementById('albumSearchResults');

        if (!results) {
            return;
        }

        try {
            const data = await safeJson(fetch(`${ALBUM_SEARCH_ENDPOINT}?q=${encodeURIComponent(query)}`, {
                cache: 'no-store'
            }));

            if (!Array.isArray(data) || data.length === 0) {
                results.innerHTML = '<div class="search-modal-empty">No albums found</div>';
                return;
            }

            results.innerHTML = data.map(renderAlbumSearchResult).join('');
        } catch (err) {
            console.error('searchAlbums failed', err);
            results.innerHTML = '<div class="search-modal-empty">Album search failed</div>';
        }
    }

    function renderAlbumSearchResult(album) {
        const rawAlbumDbValue = String(album.album || '');
        const rawAlbumArtistDbValue = String(album.albumartist || '');

        const displayAlbumName = escapeHtml(decodeHtml(rawAlbumDbValue || 'Unknown Album'));
        const displayAlbumArtist = escapeHtml(decodeHtml(rawAlbumArtistDbValue));

        const trackCount = parseInt(album.track_count || 0, 10);

        return `
            <div class="search-result-row">
                <div class="search-result-main">
                    <div class="search-result-title">${displayAlbumName}</div>
                    <div class="search-result-artist">${displayAlbumArtist}</div>
                    <div class="search-result-album">${trackCount} track${trackCount === 1 ? '' : 's'}</div>
                </div>

                <div class="search-result-actions">
                    <button type="button"
                        class="search-result-action"
                        data-album-action="play-next"
                        data-album="${encodeURIComponent(rawAlbumDbValue)}"
                        data-albumartist="${encodeURIComponent(rawAlbumArtistDbValue)}"
                        title="Play album next"
                        aria-label="Play album next">
                        ${iconChevronRight()}
                    </button>

                    <button type="button"
                        class="search-result-action"
                        data-album-action="insert-next"
                        data-album="${encodeURIComponent(rawAlbumDbValue)}"
                        data-albumartist="${encodeURIComponent(rawAlbumArtistDbValue)}"
                        title="Queue album"
                        aria-label="Queue album">
                        ${iconChevronDoubleRight()}
                    </button>

                    <button type="button"
                        class="search-result-action"
                        data-album-action="play-now"
                        data-album="${encodeURIComponent(rawAlbumDbValue)}"
                        data-albumartist="${encodeURIComponent(rawAlbumArtistDbValue)}"
                        title="Play album now"
                        aria-label="Play album now">
                        ${iconArrowRight()}
                    </button>

                    <button type="button"
                        class="search-result-action"
                        data-album-action="drill"
                        data-album="${encodeURIComponent(rawAlbumDbValue)}"
                        data-albumartist="${encodeURIComponent(rawAlbumArtistDbValue)}"
                        title="Show tracks"
                        aria-label="Show tracks">
                        ${iconChevronDown()}
                    </button>
                </div>
            </div>
        `;
    }

    function iconChevronDown() {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708"/>
            </svg>
        `;
    }

    async function openAlbumTracksPanel(album, albumartist) {
        
        els.featureModal.classList.add('search-modal-active');
        
        els.featureModalTitle.textContent = 'Album Tracks';

        els.featureModalBody.innerHTML = `
            <div class="search-modal">
                <div id="albumTrackResults" class="search-modal-results"></div>
            </div>
        `;

        const results = document.getElementById('albumTrackResults');

        try {
            const url = `${ALBUM_TRACKS_ENDPOINT}?album=${encodeURIComponent(album)}&albumartist=${encodeURIComponent(albumartist)}`;
            const data = await safeJson(fetch(url, { cache: 'no-store' }));

            if (!Array.isArray(data) || data.length === 0) {
                results.innerHTML = '<div class="search-modal-empty">No tracks found</div>';
                return;
            }

            results.innerHTML = data.map(renderTrackSearchResult).join('');
        } catch (err) {
            console.error('openAlbumTracksPanel failed', err);
            results.innerHTML = '<div class="search-modal-empty">Album tracks failed</div>';
        }
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

    function openFeatureModal(title = '', iframeSrc = '') {
        if (!els.featureModal || !els.featureModalBackdrop) {
            return;
        }

        els.featureModalTitle.textContent = title;
        els.featureModalBody.innerHTML = '';

        if (iframeSrc !== '') {
            const iframe = document.createElement('iframe');
            iframe.className = 'feature-modal-iframe';
            iframe.src = iframeSrc;
            iframe.title = title;
            els.featureModalBody.appendChild(iframe);
        }

        els.featureModal.classList.add('open');
        els.featureModalBackdrop.classList.add('open');
        els.featureModal.setAttribute('aria-hidden', 'false');

        hideGridHelper();
    }

    function closeFeatureModal() {
        if (!els.featureModal || !els.featureModalBackdrop) {
            return;
        }

        els.featureModal.classList.remove('search-modal-active');
        els.featureModal.classList.remove('open');
        els.featureModalBackdrop.classList.remove('open');
        els.featureModal.setAttribute('aria-hidden', 'true');
    }

function openSearchModal() {
    openFeatureModal('Search');

    els.featureModalBody.innerHTML = `
        <div class="search-launch-grid">
            <button type="button" class="search-launch-tile" data-search-mode="current-album" title="Current Playing Album">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1z"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="search-launch-tile" data-search-mode="track-search" title="Track Search">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M6 13c0 1.105-1.12 2-2.5 2S1 14.105 1 13s1.12-2 2.5-2 2.5.896 2.5 2m9-2c0 1.105-1.12 2-2.5 2s-2.5-.895-2.5-2 1.12-2 2.5-2 2.5.895 2.5 2"/>
                        <path fill-rule="evenodd" d="M14 11V2h1v9zM6 3v10H5V3z"/>
                        <path d="M5 2.905a1 1 0 0 1 .9-.995l8-.8a1 1 0 0 1 1.1.995V3L5 4z"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="search-launch-tile" data-search-mode="album-search" title="Album Search">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                        <path d="M8 6a2 2 0 1 0 0 4 2 2 0 0 0 0-4M4 8a4 4 0 1 1 8 0 4 4 0 0 1-8 0"/>
                        <path d="M9 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="search-launch-tile" data-search-mode="artist-search" title="Artist Search">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M3.5 6.5A.5.5 0 0 1 4 7v1a4 4 0 0 0 8 0V7a.5.5 0 0 1 1 0v1a5 5 0 0 1-4.5 4.975V15h3a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1h3v-2.025A5 5 0 0 1 3 8V7a.5.5 0 0 1 .5-.5"/>
                        <path d="M10 8a2 2 0 1 1-4 0V3a2 2 0 1 1 4 0zM8 0a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V3a3 3 0 0 0-3-3"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="search-launch-tile" data-search-mode="playlist" title="Playlist">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M12 13c0 1.105-1.12 2-2.5 2S7 14.105 7 13s1.12-2 2.5-2 2.5.895 2.5 2"/>
                        <path fill-rule="evenodd" d="M12 3v10h-1V3z"/>
                        <path d="M11 2.82a1 1 0 0 1 .804-.98l3-.6A1 1 0 0 1 16 2.22V4l-5 1z"/>
                        <path fill-rule="evenodd" d="M0 11.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5m0-4A.5.5 0 0 1 .5 7H8a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5m0-4A.5.5 0 0 1 .5 3H8a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5"/>
                    </svg>
                </span>
            </button>

            <button type="button" class="search-launch-tile" data-search-mode="track-list" title="Full Track List">
                <span class="search-launch-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5"/>
                        <path d="M1.713 11.865v-.474H2c.217 0 .363-.137.363-.317 0-.185-.158-.31-.361-.31-.223 0-.367.152-.373.31h-.59c.016-.467.373-.787.986-.787.588-.002.954.291.957.703a.595.595 0 0 1-.492.594v.033a.615.615 0 0 1 .569.631c.003.533-.502.8-1.051.8-.656 0-1-.37-1.008-.794h.582c.008.178.186.306.422.309.254 0 .424-.145.422-.35-.002-.195-.155-.348-.414-.348h-.3zm-.004-4.699h-.604v-.035c0-.408.295-.844.958-.844.583 0 .96.326.96.756 0 .389-.257.617-.476.848l-.537.572v.03h1.054V9H1.143v-.395l.957-.99c.138-.142.293-.304.293-.508 0-.18-.147-.32-.342-.32a.33.33 0 0 0-.342.338zM2.564 5h-.635V2.924h-.031l-.598.42v-.567l.629-.443h.635z"/>
                    </svg>
                </span>
            </button>
        </div>
    `;
}

function openTrackSearchPanel() {
    
    els.featureModal.classList.add('search-modal-active');
    
    els.featureModalTitle.textContent = 'Track Search';

    els.featureModalBody.innerHTML = `
        <div class="search-modal">
            <input
                id="trackSearchInput"
                class="search-modal-input"
                type="search"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                placeholder=""
            >

            <div id="trackSearchResults" class="search-modal-results"></div>
        </div>
    `;

    const input = document.getElementById('trackSearchInput');
    const results = document.getElementById('trackSearchResults');

    window.setTimeout(() => {
        input.focus();
    }, 80);

    input.addEventListener('input', () => {
        const query = input.value.trim();

        if (state.searchTimeoutHandle !== null) {
            window.clearTimeout(state.searchTimeoutHandle);
            state.searchTimeoutHandle = null;
        }

        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }

        state.searchTimeoutHandle = window.setTimeout(() => {
            searchTracks(query);
        }, SEARCH_DEBOUNCE_MS);
    });
}


    async function searchTracks(query) {
        const results = document.getElementById('trackSearchResults');

        if (!results) {
            return;
        }

        try {
            const url = `${TRACK_SEARCH_ENDPOINT}?q=${encodeURIComponent(query)}`;
            const data = await safeJson(fetch(url, { cache: 'no-store' }));

            if (!Array.isArray(data) || data.length === 0) {
                results.innerHTML = '<div class="search-modal-empty">No tracks found</div>';
                return;
            }

            results.innerHTML = data.map(renderTrackSearchResult).join('');
        } catch (err) {
            console.error('searchTracks failed', err);
            results.innerHTML = '<div class="search-modal-empty">Search failed</div>';
        }
    }

    function renderTrackSearchResult(track) {
        const id = parseInt(track.id || 0, 10);
        const title = cleanText(track.title || 'Unknown Title');
        const artist = cleanText(track.artist || '');
        const album = cleanText(track.album || '');

        return `
            <div class="search-result-row" data-track-id="${id}">
                <div class="search-result-main">
                    <div class="search-result-title">${title}</div>
                    <div class="search-result-artist">${artist}</div>
                    <div class="search-result-album">${album}</div>
                </div>

                <div class="search-result-actions">
                    <button type="button" class="search-result-action" data-search-action="play-next" data-track-id="${id}" title="Play next" aria-label="Play next">
                        ${iconChevronRight()}
                    </button>

                    <button type="button" class="search-result-action" data-search-action="insert-next" data-track-id="${id}" title="Queue next" aria-label="Queue next">
                        ${iconChevronDoubleRight()}
                    </button>

                    <button type="button" class="search-result-action" data-search-action="play-now" data-track-id="${id}" title="Play now" aria-label="Play now">
                        ${iconArrowRight()}
                    </button>
                </div>
            </div>
        `;
    }

    function iconChevronRight() {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M6.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L11.793 8 6.646 2.354a.5.5 0 0 1 0-.708"/>
            </svg>
        `;
    }

    function iconChevronDoubleRight() {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L8.793 8 3.646 2.354a.5.5 0 0 1 0-.708"/>
                <path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L12.793 8 7.646 2.354a.5.5 0 0 1 0-.708"/>
            </svg>
        `;
    }

    function iconArrowRight() {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
        `;
    }

    function handleSearchResultAction(action, trackId) {
        if (!trackId) {
            return;
        }

        if (action === 'play-next') {
            setMessage('Play next action not wired yet');
            closeFeatureModal();
            return;
        }

        if (action === 'insert-next') {
            setMessage('Queue next action not wired yet');
            return;
        }

        if (action === 'play-now') {
            setMessage('Play now action not wired yet');
            closeFeatureModal();
            return;
        }
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
        
        if (data.next_title) {
            const nextArtist = data.next_artist ? ` - ${data.next_artist}` : '';
            els.nextTrack.textContent = `Next: ${data.next_title}${nextArtist}`;
        } else {
            els.nextTrack.textContent = '';
        }
        
        
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
    
    async function handleSearchResultAction(action, trackId) {
    if (!trackId) {
        return;
    }

    let apiAction = '';

    if (action === 'play-next') {
        apiAction = 'play_next';
    }

    if (action === 'insert-next') {
        apiAction = 'queue';
    }

    if (action === 'play-now') {
        apiAction = 'play_now';
    }

    if (apiAction === '') {
        return;
    }

    try {
        const data = await safeJson(fetch(TRACK_ACTION_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            cache: 'no-store',
            body: JSON.stringify({
                action: apiAction,
                track_id: trackId
            })
        }));

        if (!data || data.status !== 'ok') {
            console.error('track action failed', data);
            setMessage('Track action failed');
            return;
        }

        if (apiAction === 'queue') {
            setMessage('Track queued');
            await fetchMeta(true);
            return;
        }

        closeFeatureModal();
        setMessage('');
        await fetchMeta(true);

    } catch (err) {
        console.error('handleSearchResultAction failed', err);
        setMessage('Track action failed');
    }
}

function openArtistSearchPanel() {
    els.featureModal.classList.add('search-modal-active');
    els.featureModalTitle.textContent = 'Artist Search';

    els.featureModalBody.innerHTML = `
        <div class="search-modal">
            <input
                id="artistSearchInput"
                class="search-modal-input"
                type="search"
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false"
                placeholder=""
            >

            <div id="artistSearchResults" class="search-modal-results"></div>
        </div>
    `;

    const input = document.getElementById('artistSearchInput');
    const results = document.getElementById('artistSearchResults');

    window.setTimeout(() => {
        input.focus();
    }, 80);

    input.addEventListener('input', () => {
        const query = input.value.trim();

        if (state.searchTimeoutHandle !== null) {
            window.clearTimeout(state.searchTimeoutHandle);
            state.searchTimeoutHandle = null;
        }

        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }

        state.searchTimeoutHandle = window.setTimeout(() => {
            searchArtists(query);
        }, SEARCH_DEBOUNCE_MS);
    });
}

async function searchArtists(query) {
    const results = document.getElementById('artistSearchResults');

    if (!results) {
        return;
    }

    try {
        const data = await safeJson(fetch(`${ARTIST_SEARCH_ENDPOINT}?q=${encodeURIComponent(query)}`, {
            cache: 'no-store'
        }));

        if (!Array.isArray(data) || data.length === 0) {
            results.innerHTML = '<div class="search-modal-empty">No artists found</div>';
            return;
        }

        results.innerHTML = data.map(renderArtistSearchResult).join('');
    } catch (err) {
        console.error('searchArtists failed', err);
        results.innerHTML = '<div class="search-modal-empty">Artist search failed</div>';
    }
}

function renderArtistSearchResult(artist) {
    const rawArtistDbValue = String(artist.artist || '');
    const displayArtist = cleanText(rawArtistDbValue || 'Unknown Artist');
    const albumCount = parseInt(artist.album_count || 0, 10);
    const trackCount = parseInt(artist.track_count || 0, 10);

    return `
        <div class="search-result-row">
            <div class="search-result-main">
                <div class="search-result-title">${displayArtist}</div>
                <div class="search-result-artist">${albumCount} album${albumCount === 1 ? '' : 's'}</div>
                <div class="search-result-album">${trackCount} track${trackCount === 1 ? '' : 's'}</div>
            </div>

            <div class="search-result-actions">
                <button type="button"
                    class="search-result-action"
                    data-artist-action="drill"
                    data-artist="${encodeURIComponent(rawArtistDbValue)}"
                    title="Show albums"
                    aria-label="Show albums">
                    ${iconChevronDown()}
                </button>
            </div>
        </div>
    `;
}

async function openArtistAlbumsPanel(artist) {
    els.featureModal.classList.add('search-modal-active');
    els.featureModalTitle.textContent = 'Artist Albums';

    els.featureModalBody.innerHTML = `
        <div class="search-modal">
            <div id="artistAlbumResults" class="search-modal-results"></div>
        </div>
    `;

    const results = document.getElementById('artistAlbumResults');

    try {
        const data = await safeJson(fetch(`${ARTIST_ALBUMS_ENDPOINT}?artist=${encodeURIComponent(artist)}`, {
            cache: 'no-store'
        }));

        if (!Array.isArray(data) || data.length === 0) {
            results.innerHTML = '<div class="search-modal-empty">No albums found</div>';
            return;
        }

        results.innerHTML = data.map(renderAlbumSearchResult).join('');
    } catch (err) {
        console.error('openArtistAlbumsPanel failed', err);
        results.innerHTML = '<div class="search-modal-empty">Artist albums failed</div>';
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

    function decodeHtml(value) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = String(value ?? '');
        return textarea.value;
    }

    function cleanText(value) {
        return escapeHtml(decodeHtml(value));
    }

    function bindEvents() {
        
        
        
        async function openCurrentAlbumPanel() {
    const currentAlbum = state.ui.album || '';
    const currentArtist = state.ui.artist || '';

    if (currentAlbum === '') {
        setMessage('No current album available');
        return;
    }

    els.featureModal.classList.add('search-modal-active');
    els.featureModalTitle.textContent = 'Current Album';

    els.featureModalBody.innerHTML = `
        <div class="search-modal">
            <div id="currentAlbumResults" class="search-modal-results"></div>
        </div>
    `;

    const results = document.getElementById('currentAlbumResults');

    try {
        const url = `${ALBUM_TRACKS_ENDPOINT}?album=${encodeURIComponent(currentAlbum)}&albumartist=${encodeURIComponent(currentArtist)}`;
        const data = await safeJson(fetch(url, { cache: 'no-store' }));

        if (!Array.isArray(data) || data.length === 0) {
            results.innerHTML = '<div class="search-modal-empty">No tracks found</div>';
            return;
        }

        results.innerHTML = data.map(renderTrackSearchResult).join('');
    } catch (err) {
        console.error('openCurrentAlbumPanel failed', err);
        results.innerHTML = '<div class="search-modal-empty">Current album failed</div>';
    }
}
        
        
        
        
        
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

        document.addEventListener('click', (event) => {
            const actionButton = event.target.closest('.search-result-action');

            if (!actionButton) {
                return;
            }

            const action = actionButton.dataset.searchAction || '';
            const trackId = parseInt(actionButton.dataset.trackId || '0', 10);

            handleSearchResultAction(action, trackId);
        });

        document.addEventListener('click', (event) => {
            const tile = event.target.closest('.search-launch-tile');

            if (!tile) {
                return;
            }

            const mode = tile.dataset.searchMode || '';

            if (mode === 'current-album') {
                openCurrentAlbumPanel();
            }

            if (mode === 'track-search') {
                openTrackSearchPanel();
            }
            
            if (mode === 'album-search') {
                openAlbumSearchPanel();
            }

            if (mode === 'artist-search') {
                openArtistSearchPanel();
            }
        });

        document.addEventListener('click', async (event) => {
            const artistButton = event.target.closest('[data-artist-action]');

            if (!artistButton) {
                return;
            }

            const action = artistButton.dataset.artistAction || '';
            const artist = decodeURIComponent(artistButton.dataset.artist || '');

            if (action === 'drill') {
                await openArtistAlbumsPanel(artist);
            }
        });


        els.zones.forEach((zone) => {
            zone.addEventListener('click', async () => {
                const action = zone.dataset.zone || '';

                switch (action) {
                    case 'refresh':
                        await fetchMeta(true);
                        break;

                    case 'search':
                        openSearchModal();
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
                        openFeatureModal('Renderers', '/renderers.php');
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
                hideGridHelper();
                closeFeatureModal();
            }
        });
        
            document.addEventListener('click', async (event) => {
        const albumButton = event.target.closest('[data-album-action]');

        if (!albumButton) {
            return;
        }

        const action = albumButton.dataset.albumAction || '';
        const album = decodeURIComponent(albumButton.dataset.album || '');
        const albumartist = decodeURIComponent(albumButton.dataset.albumartist || '');

        if (action === 'drill') {
            await openAlbumTracksPanel(album, albumartist);
            return;
        }

        await handleAlbumResultAction(action, album, albumartist);
    });
    }

    async function handleAlbumResultAction(action, album, albumartist) {
        let apiAction = '';

        if (action === 'play-next') {
            apiAction = 'play_next';
        }

        if (action === 'insert-next') {
            apiAction = 'queue';
        }

        if (action === 'play-now') {
            apiAction = 'play_now';
        }

        if (apiAction === '') {
            return;
        }

        try {
            const data = await safeJson(fetch(ALBUM_ACTION_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                cache: 'no-store',
                body: JSON.stringify({
                    action: apiAction,
                    album: album,
                    albumartist: albumartist
                })
            }));

            if (!data || data.status !== 'ok') {
                console.error('album action failed', data);
                setMessage('Album action failed');
                return;
            }

            if (apiAction === 'queue') {
                setMessage('Album queued');
                await fetchMeta(true);
                return;
            }

            closeFeatureModal();
            setMessage('');
            await fetchMeta(true);

        } catch (err) {
            console.error('handleAlbumResultAction failed', err);
            setMessage('Album action failed');
        }
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