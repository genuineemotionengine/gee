const GeeSpaces = (() => {
    'use strict';

    const API_ENDPOINT = '/api/spaces.php';

    const state = {
        spaces: null,
        mode: 'multiroom',
        isOpen: false,
        isBusy: false
    };

    const els = {};
    
        function geeFormatStreamName(stream) {
        return String(stream || 'safe').toLowerCase() === 'hires' ? 'Hires' : 'Safe';
    }

    function geeUpdatePlayerContextFromSpaces(data) {
        if (!data || !data.current) {
            return;
        }

        const current = data.current;
        let name = '';

        if (current.space_type === 'room') {
            const room = (data.rooms || []).find(r => r.room_id === current.space_id);
            name = room ? room.room_name : current.space_id;
        } else {
            const renderer = (data.renderers || []).find(r => r.renderer_id === current.space_id);
            name = renderer ? renderer.renderer_name : current.space_id;
        }

        if (!name) {
            name = 'No listening space selected';
        }

        const label = name === 'No listening space selected'
            ? name
            : `${name} - ${geeFormatStreamName(current.active_stream)}`;

        document.querySelectorAll(
            '#currentRenderer, #current-renderer, .current-renderer, [data-gee-current-renderer]'
        ).forEach(el => {
            el.textContent = `Current: ${label}`;
        });
    }

    function cacheElements() {
        els.modal = document.getElementById('featureModal');
        els.backdrop = document.getElementById('featureModalBackdrop');
        els.title = document.getElementById('featureModalTitle');
        els.body = document.getElementById('featureModalBody');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function titleCaseStream(stream) {
        return stream === 'hires' ? 'Hires' : 'Safe';
    }

    function rendererName(rendererId) {
        const renderer = (state.spaces?.renderers || []).find((item) => item.renderer_id === rendererId);
        return renderer?.renderer_name || renderer?.hostname || rendererId;
    }

    function currentName() {
        const current = state.spaces?.current || {};

        if (current.space_type === 'room') {
            const room = (state.spaces?.rooms || []).find((item) => item.room_id === current.space_id);
            return room?.room_name || current.space_id || 'No space selected';
        }

        if (current.space_type === 'renderer') {
            return rendererName(current.space_id) || 'No renderer selected';
        }

        return 'No space selected';
    }

    async function api(action, params = {}) {
        const url = new URL(API_ENDPOINT, window.location.origin);
        url.searchParams.set('action', action);

        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                url.searchParams.set(key, String(value));
            }
        });

        const response = await fetch(url.toString(), {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.success !== true) {
            const message = data?.message || `Spaces action failed: ${action}`;
            throw new Error(message);
        }

        return data;
    }

    function setBusy(isBusy) {
        state.isBusy = isBusy;

        if (!els.body) {
            return;
        }

        els.body.classList.toggle('spaces-busy', isBusy);

        const controls = els.body.querySelectorAll('button, select, input');
        controls.forEach((control) => {
            control.disabled = isBusy;
        });
    }

    function setStatus(message = '', tone = '') {
        const status = document.getElementById('spacesStatus');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.className = 'spaces-status';

        if (tone !== '') {
            status.classList.add(`spaces-status-${tone}`);
        }
    }

    async function loadSpaces() {
        state.spaces = await api('list');
        render();
    }

    async function runAction(action, params = {}, successMessage = '') {
        try {
            setBusy(true);
            setStatus('Updating…');

            await api(action, params);
            await loadSpaces();

            setStatus(successMessage || 'Updated', 'ok');
        } catch (err) {
            console.error('GeeSpaces action failed', err);
            setStatus(err.message || 'Action failed', 'error');
        } finally {
            setBusy(false);
        }
    }

    function streamButtons(type, id, activeStream) {
        const action = type === 'room' ? 'select-room' : 'select-renderer';
        const idAttribute = type === 'room' ? 'room-id' : 'renderer-id';

        return `
            <div class="spaces-stream-actions" role="group" aria-label="Stream selection">
                <button
                    type="button"
                    class="spaces-pill ${activeStream === 'safe' ? 'active' : ''}"
                    data-spaces-action="${action}"
                    data-${idAttribute}="${escapeHtml(id)}"
                    data-stream="safe"
                >Safe</button>

                <button
                    type="button"
                    class="spaces-pill ${activeStream === 'hires' ? 'active' : ''}"
                    data-spaces-action="${action}"
                    data-${idAttribute}="${escapeHtml(id)}"
                    data-stream="hires"
                >Hires</button>
            </div>
        `;
    }

    function renderCurrent() {
        const current = state.spaces?.current || {};
        const name = currentName();
        const stream = current.active_stream || 'safe';

        return `
            <section class="spaces-current-card">
                <div class="spaces-eyebrow">Current Listening Space</div>
                <div class="spaces-current-main">
                    <div>
                        <div class="spaces-current-name">${escapeHtml(name)}</div>
                        <div class="spaces-current-sub">${escapeHtml(titleCaseStream(stream))}</div>
                    </div>
                </div>
            </section>
        `;
    }

    function renderRooms() {
        const rooms = state.spaces?.rooms || [];
        const standalone = state.spaces?.standalone_renderers || [];

        if (rooms.length === 0) {
            return `
                <section class="spaces-section">
                    <div class="spaces-section-title">Rooms</div>
                    <div class="spaces-empty">No rooms yet. Create one below.</div>
                </section>
            `;
        }

        const cards = rooms.map((room) => {
            const members = Array.isArray(room.members) ? room.members : [];
            const memberNames = members.map((rendererId) => rendererName(rendererId));
            const activeStream = room.active_stream || 'safe';
            const addOptions = standalone.map((renderer) => `
                <option value="${escapeHtml(renderer.renderer_id)}">${escapeHtml(renderer.renderer_name || renderer.hostname || renderer.renderer_id)}</option>
            `).join('');

            const memberRows = members.length > 0
                ? members.map((rendererId) => `
                    <div class="spaces-member-row">
                        <span>${escapeHtml(rendererName(rendererId))}</span>
                        <button
                            type="button"
                            class="spaces-link-button"
                            data-spaces-action="remove-renderer"
                            data-room-id="${escapeHtml(room.room_id)}"
                            data-renderer-id="${escapeHtml(rendererId)}"
                        >Remove</button>
                    </div>
                `).join('')
                : '<div class="spaces-muted">No renderers in this room.</div>';

            return `
                <article class="spaces-card">
                    <div class="spaces-card-header">
                        <div>
                            <div class="spaces-card-title">${escapeHtml(room.room_name)}</div>
                            <div class="spaces-card-sub">${memberNames.length ? escapeHtml(memberNames.join(', ')) : 'Empty room'}</div>
                        </div>
                        ${streamButtons('room', room.room_id, activeStream)}
                    </div>

                    <div class="spaces-member-list">
                        ${memberRows}
                    </div>

                    <div class="spaces-add-row">
                        <select class="spaces-select" data-add-room-select="${escapeHtml(room.room_id)}" ${standalone.length === 0 ? 'disabled' : ''}>
                            ${standalone.length === 0 ? '<option>No standalone renderers</option>' : addOptions}
                        </select>
                        <button
                            type="button"
                            class="spaces-secondary-button"
                            data-spaces-action="add-renderer-from-select"
                            data-room-id="${escapeHtml(room.room_id)}"
                            ${standalone.length === 0 ? 'disabled' : ''}
                        >Add</button>
                    </div>
                </article>
            `;
        }).join('');

        return `
            <section class="spaces-section">
                <div class="spaces-section-title">Rooms</div>
                ${cards}
            </section>
        `;
    }

    function renderStandaloneRenderers() {
        const renderers = state.spaces?.standalone_renderers || [];
        const current = state.spaces?.current || {};

        if (renderers.length === 0) {
            return `
                <section class="spaces-section">
                    <div class="spaces-section-title">Standalone Renderers</div>
                    <div class="spaces-empty">All renderers are currently assigned to rooms.</div>
                </section>
            `;
        }

        const rows = renderers.map((renderer) => {
            const activeStream = current.space_type === 'renderer' && current.space_id === renderer.renderer_id
                ? current.active_stream || 'safe'
                : 'safe';

            return `
                <article class="spaces-renderer-row">
                    <div>
                        <div class="spaces-card-title">${escapeHtml(renderer.renderer_name || renderer.hostname || renderer.renderer_id)}</div>
                        <div class="spaces-card-sub">${escapeHtml(renderer.model || renderer.ip_address || '')}</div>
                    </div>
                    ${streamButtons('renderer', renderer.renderer_id, activeStream)}
                </article>
            `;
        }).join('');

        return `
            <section class="spaces-section">
                <div class="spaces-section-title">Standalone Renderers</div>
                ${rows}
            </section>
        `;
    }

    function renderRegisteredRenderers() {
        const renderers = state.spaces?.renderers || [];
        const roomLookup = new Map();

        (state.spaces?.rooms || []).forEach((room) => {
            (room.members || []).forEach((rendererId) => {
                roomLookup.set(rendererId, room.room_name || room.room_id);
            });
        });

        if (renderers.length === 0) {
            return `
                <div class="spaces-panel">
                    <section class="spaces-section">
                        <div class="spaces-empty">No registered renderers found.</div>
                    </section>
                </div>
            `;
        }

        const rows = renderers.map((renderer) => {
            const assignedRoom = roomLookup.get(renderer.renderer_id);
            const status = assignedRoom ? `Room: ${assignedRoom}` : 'Standalone';
            const meta = [renderer.model, renderer.ip_address].filter(Boolean).join(' · ');

            return `
                <article class="spaces-renderer-row spaces-renderer-row-readonly">
                    <div>
                        <div class="spaces-card-title">${escapeHtml(renderer.renderer_name || renderer.hostname || renderer.renderer_id)}</div>
                        <div class="spaces-card-sub">${escapeHtml(meta)}</div>
                        <div class="spaces-muted">${escapeHtml(status)}</div>
                    </div>
                </article>
            `;
        }).join('');

        return `
            <div class="spaces-panel">
                <section class="spaces-current-card">
                    <div class="spaces-eyebrow">Registered Renderers</div>
                    <div class="spaces-current-main">
                        <div>
                            <div class="spaces-current-name">${renderers.length}</div>
                            <div class="spaces-current-sub">Connected to Gee Core</div>
                        </div>
                    </div>
                </section>

                <section class="spaces-section">
                    <div class="spaces-section-title">Renderers</div>
                    ${rows}
                </section>
            </div>
        `;
    }

    function renderCreateRoom() {
        return `
            <section class="spaces-section spaces-create-room">
                <div class="spaces-section-title">Create Room</div>
                <form id="spacesCreateRoomForm" class="spaces-create-form">
                    <input
                        id="spacesCreateRoomInput"
                        class="spaces-input"
                        type="text"
                        autocomplete="off"
                        placeholder="Room name"
                    >
                    <button type="submit" class="spaces-primary-button">Create</button>
                </form>
            </section>
        `;
    }

    function renderMultiroom() {
        return `
            <div class="spaces-panel">
                ${renderCurrent()}
                <div id="spacesStatus" class="spaces-status"></div>
                ${renderRooms()}
                ${renderStandaloneRenderers()}
                ${renderCreateRoom()}
            </div>
        `;
    }

    function render() {
        if (!els.body) {
            return;
        }

        els.body.innerHTML = state.mode === 'renderers'
            ? renderRegisteredRenderers()
            : renderMultiroom();
    }

    async function open(mode = 'multiroom') {
        cacheElements();

        if (!els.modal || !els.backdrop || !els.title || !els.body) {
            return;
        }

        state.isOpen = true;
        state.mode = mode;

        els.title.textContent = mode === 'renderers' ? 'Registered Renderers' : 'Listening Spaces';
        els.body.innerHTML = '<div class="spaces-loading">Loading…</div>';

        els.modal.classList.add('open', 'search-modal-active', 'spaces-modal-active');
        els.backdrop.classList.add('open');
        els.modal.setAttribute('aria-hidden', 'false');

        try {
            await loadSpaces();
        } catch (err) {
            console.error('GeeSpaces open failed', err);
            els.body.innerHTML = `<div class="spaces-empty spaces-error">${escapeHtml(err.message || 'Failed to load listening spaces')}</div>`;
        }
    }

    async function handleClick(event) {
        const actionButton = event.target.closest('[data-spaces-action]');

        if (!actionButton || !els.body || !els.body.contains(actionButton) || state.mode !== 'multiroom') {
            return;
        }

        event.preventDefault();

        const action = actionButton.dataset.spacesAction || '';

        if (action === 'select-room') {
            await runAction('select-room', {
                room_id: actionButton.dataset.roomId || '',
                stream: actionButton.dataset.stream || 'safe'
            }, 'Room selected');
            return;
        }

        if (action === 'select-renderer') {
            await runAction('select-renderer', {
                renderer_id: actionButton.dataset.rendererId || '',
                stream: actionButton.dataset.stream || 'safe'
            }, 'Renderer selected');
            return;
        }

        if (action === 'remove-renderer') {
            await runAction('remove-renderer', {
                room_id: actionButton.dataset.roomId || '',
                renderer_id: actionButton.dataset.rendererId || ''
            }, 'Renderer removed');
            return;
        }

        if (action === 'add-renderer-from-select') {
            const roomId = actionButton.dataset.roomId || '';
            const select = els.body.querySelector(`[data-add-room-select="${CSS.escape(roomId)}"]`);
            const rendererId = select?.value || '';

            if (rendererId === '') {
                setStatus('Choose a renderer first', 'error');
                return;
            }

            await runAction('add-renderer', {
                room_id: roomId,
                renderer_id: rendererId
            }, 'Renderer added');
        }
    }

    async function handleSubmit(event) {
        const form = event.target.closest('#spacesCreateRoomForm');

        if (!form || state.mode !== 'multiroom') {
            return;
        }

        event.preventDefault();

        const input = document.getElementById('spacesCreateRoomInput');
        const roomName = (input?.value || '').trim();

        if (roomName === '') {
            setStatus('Enter a room name', 'error');
            return;
        }

        await runAction('create-room', { room_name: roomName }, 'Room created');
    }

    function interceptLegacyRendererModal(event) {
        const multiroomZone = event.target.closest('.zone[data-zone="multiroom"]');
        const renderersZone = event.target.closest('.zone[data-zone="renderers"]');

        if (!multiroomZone && !renderersZone) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        if (multiroomZone) {
            open('multiroom');
            return;
        }

        open('renderers');
    }

    function init() {
        document.addEventListener('click', interceptLegacyRendererModal, true);
        document.addEventListener('click', handleClick);
        document.addEventListener('submit', handleSubmit);
    }

    return {
        init,
        open
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    GeeSpaces.init();
});

// ------------------------------------------------------------
// Gee Listening Space Context Line
// Spaces is now authoritative for #stream and #renderer.
// This intentionally overrides old renderer/runtime labels from gee-player.js.
// ------------------------------------------------------------

let geeLastSpacesContext = null;

function geeFormatSpaceStream(stream) {
    return String(stream || 'safe').toLowerCase() === 'hires' ? 'Hires' : 'Safe';
}

function geeFindCurrentSpaceName(data) {
    if (!data || !data.current) {
        return 'No listening space selected';
    }

    const current = data.current;

    if (current.space_type === 'room') {
        const room = (data.rooms || []).find(function (r) {
            return r.room_id === current.space_id;
        });

        return room ? room.room_name : current.space_id;
    }

    if (current.space_type === 'renderer') {
        const renderer = (data.renderers || []).find(function (r) {
            return r.renderer_id === current.space_id;
        });

        return renderer ? renderer.renderer_name : current.space_id;
    }

    return 'No listening space selected';
}

function geeApplyPlayerContextLine(data) {
    if (!data || !data.success || !data.current) {
        return;
    }

    const streamEl = document.getElementById('stream');
    const rendererEl = document.getElementById('renderer');

    if (!streamEl || !rendererEl) {
        return;
    }

    const spaceName = geeFindCurrentSpaceName(data);
    const streamName = geeFormatSpaceStream(data.current.active_stream);

    streamEl.textContent = streamName;
    rendererEl.textContent = spaceName;

    geeLastSpacesContext = {
        stream: streamName,
        renderer: spaceName
    };
}

function geeReapplyLastSpacesContextLine() {
    if (!geeLastSpacesContext) {
        return;
    }

    const streamEl = document.getElementById('stream');
    const rendererEl = document.getElementById('renderer');

    if (streamEl) {
        streamEl.textContent = geeLastSpacesContext.stream;
    }

    if (rendererEl) {
        rendererEl.textContent = geeLastSpacesContext.renderer;
    }
}

async function geeUpdatePlayerContextLine() {
    try {
        const response = await fetch('/api/spaces.php?action=list', {
            cache: 'no-store'
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        geeApplyPlayerContextLine(data);

    } catch (error) {
        console.warn('Gee context line update failed:', error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    geeUpdatePlayerContextLine();

    // Pull current space state regularly.
    setInterval(geeUpdatePlayerContextLine, 5000);

    // Re-apply spaces context after gee-player.js refreshes metadata.
    setInterval(geeReapplyLastSpacesContextLine, 500);
});