/**
 * GeeSpaces — Listening Spaces / Multi-room controller
 *
 * Speed improvements over previous version:
 *   1. Optimistic UI — select-room and select-renderer update the pill
 *      appearance instantly (0ms) without waiting for the API response.
 *      The player context line and confirmed state follow once the server
 *      responds. If the server returns an error the UI reverts.
 *   2. Single round-trip for structural actions — create-room, delete-room,
 *      add-renderer, remove-renderer all call the action then immediately
 *      re-fetch the list in one sequential chain, but select-room and
 *      select-renderer skip the re-fetch entirely and update state from the
 *      action response directly.
 *   3. Custom dark confirmation dialog — replaces the jarring white browser
 *      confirm() with an in-modal panel that matches the app colour palette.
 */

const GeeSpaces = (() => {
    'use strict';

    const API  = '/api/spaces.php';
    const state = { data: null, mode: 'multiroom', busy: false };

    const $ = id => document.getElementById(id);

    function esc(v) {
        return String(v ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    // ── API ──────────────────────────────────────────────────────────────────

    async function callApi(action, params = {}) {
        const url = new URL(API, location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => {
            if (v != null) url.searchParams.set(k, String(v));
        });
        const res  = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.success !== true) {
            throw new Error(data?.message || `Spaces error: ${action}`);
        }
        return data;
    }

    async function loadData() {
        state.data = await callApi('list');
    }

    // ── Player context line ───────────────────────────────────────────────────

    function updatePlayerContext() {
        const d = state.data;
        if (!d?.current) return;
        const cur    = d.current;
        const stream = cur.active_stream === 'hires' ? 'Hires' : 'Safe';
        let   name   = 'No space selected';
        if (cur.space_type === 'room') {
            const room = (d.rooms || []).find(r => r.room_id === cur.space_id);
            name = room?.room_name || cur.space_id;
        } else if (cur.space_type === 'renderer') {
            const r = (d.renderers || []).find(r => r.renderer_id === cur.space_id);
            name = r?.renderer_name || r?.hostname || cur.space_id;
        }
        const streamEl   = $('stream');
        const rendererEl = $('renderer');
        if (streamEl)   streamEl.textContent   = stream;
        if (rendererEl) rendererEl.textContent = name;
    }

    // ── Modal open / close ────────────────────────────────────────────────────

    function openModal(mode) {
        const modal    = $('featureModal');
        const backdrop = $('featureModalBackdrop');
        const title    = $('featureModalTitle');
        const body     = $('featureModalBody');
        if (!modal) return;
        state.mode = mode;
        modal.className = modal.className.replace(/\bsearch-modal-active\b|\bspaces-modal-active\b/g,'').trim();
        modal.classList.add('open', 'spaces-modal-active');
        backdrop.classList.add('open');
        modal.setAttribute('aria-hidden','false');
        title.textContent = mode === 'renderers' ? 'Registered Renderers' : 'Listening Spaces';
        body.innerHTML    = '<div class="spaces-loading">Loading\u2026</div>';
    }

    function closeModal() {
        const modal    = $('featureModal');
        const backdrop = $('featureModalBackdrop');
        if (!modal) return;
        modal.classList.remove('open','search-modal-active','spaces-modal-active');
        backdrop.classList.remove('open');
        modal.setAttribute('aria-hidden','true');
    }

    // ── Status bar ────────────────────────────────────────────────────────────

    function setStatus(msg = '', tone = '') {
        const el = $('spacesStatus');
        if (!el) return;
        el.textContent = msg;
        el.className   = 'spaces-status' + (tone ? ` spaces-status-${tone}` : '');
    }

    function setBusy(busy) {
        state.busy = busy;
        const body = $('featureModalBody');
        if (!body) return;
        body.classList.toggle('spaces-busy', busy);
        body.querySelectorAll('button, select, input').forEach(el => { el.disabled = busy; });
    }

    // ── Custom confirm dialog ─────────────────────────────────────────────────
    // Replaces the jarring white browser confirm() with an on-brand dark panel.

    function showConfirm(message) {
        return new Promise(resolve => {
            // Dismiss any existing confirm
            document.querySelectorAll('.spaces-confirm-overlay').forEach(el => el.remove());

            const overlay = document.createElement('div');
            overlay.className = 'spaces-confirm-overlay';
            overlay.innerHTML = `
                <div class="spaces-confirm-panel">
                    <p class="spaces-confirm-message">${esc(message)}</p>
                    <div class="spaces-confirm-actions">
                        <button class="spaces-confirm-cancel">Cancel</button>
                        <button class="spaces-confirm-ok">Delete</button>
                    </div>
                </div>`;

            const body = $('featureModalBody');
            if (!body) { resolve(false); return; }
            body.appendChild(overlay);

            overlay.querySelector('.spaces-confirm-cancel').addEventListener('click', () => {
                overlay.remove();
                resolve(false);
            });
            overlay.querySelector('.spaces-confirm-ok').addEventListener('click', () => {
                overlay.remove();
                resolve(true);
            });
        });
    }

    // ── Render helpers ────────────────────────────────────────────────────────

    function rendererName(id) {
        const r = (state.data?.renderers || []).find(r => r.renderer_id === id);
        return r?.renderer_name || r?.hostname || id;
    }

    function roomName(id) {
        const room = (state.data?.rooms || []).find(r => r.room_id === id);
        return room?.room_name || id;
    }

    function streamPills(type, id, activeStream) {
        const action = type === 'room' ? 'select-room' : 'select-renderer';
        const idAttr = type === 'room' ? `data-room-id="${esc(id)}"` : `data-renderer-id="${esc(id)}"`;
        return `
            <div class="spaces-stream-actions" role="group" aria-label="Stream quality">
                <button class="spaces-pill${activeStream === 'safe'  ? ' active' : ''}"
                    data-action="${action}" ${idAttr} data-stream="safe">Safe</button>
                <button class="spaces-pill${activeStream === 'hires' ? ' active' : ''}"
                    data-action="${action}" ${idAttr} data-stream="hires">Hires</button>
            </div>`;
    }

    // ── Optimistic pill update ────────────────────────────────────────────────
    // Call this BEFORE the API request so the UI responds at 0ms.

    function applyOptimisticPill(btn) {
        const group = btn.closest('.spaces-stream-actions');
        if (!group) return;
        group.querySelectorAll('.spaces-pill').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
    }

    function revertOptimisticPill(btn) {
        const group = btn.closest('.spaces-stream-actions');
        if (!group) return;
        // Restore from state.data
        const action = btn.dataset.action;
        let   id     = btn.dataset.roomId || btn.dataset.rendererId || '';
        let   stream = 'safe';

        if (action === 'select-room') {
            const room = (state.data?.rooms || []).find(r => r.room_id === id);
            stream = room?.active_stream || 'safe';
        } else if (action === 'select-renderer') {
            const cur = state.data?.current;
            if (cur?.space_type === 'renderer' && cur?.space_id === id) {
                stream = cur.active_stream || 'safe';
            }
        }
        group.querySelectorAll('.spaces-pill').forEach(p => {
            p.classList.toggle('active', p.dataset.stream === stream);
        });
    }

    // ── Renderers-mode render ─────────────────────────────────────────────────

    function renderRegisteredRenderers() {
        const renderers  = state.data?.renderers || [];
        const roomLookup = new Map();
        (state.data?.rooms || []).forEach(room => {
            (room.members || []).forEach(id => roomLookup.set(id, room.room_name || room.room_id));
        });
        if (renderers.length === 0) {
            return `<div class="spaces-panel"><div class="spaces-empty">No registered renderers found.</div></div>`;
        }
        const rows = renderers.map(r => {
            const assignedRoom = roomLookup.get(r.renderer_id);
            const status       = assignedRoom ? `Room: ${assignedRoom}` : 'Standalone';
            const meta         = [r.model, r.ip_address].filter(Boolean).join(' \u00b7 ');
            return `
                <article class="spaces-renderer-row spaces-renderer-row-readonly">
                    <div>
                        <div class="spaces-card-title">${esc(r.renderer_name || r.hostname || r.renderer_id)}</div>
                        <div class="spaces-card-sub">${esc(meta)}</div>
                        <div class="spaces-muted">${esc(status)}</div>
                    </div>
                </article>`;
        }).join('');
        return `<div class="spaces-panel">
            <section class="spaces-current-card">
                <div class="spaces-eyebrow">Registered Renderers</div>
                <div class="spaces-current-main"><div>
                    <div class="spaces-current-name">${renderers.length}</div>
                    <div class="spaces-current-sub">Connected to Gee Core</div>
                </div></div>
            </section>
            <section class="spaces-section">
                <div class="spaces-section-title">Renderers</div>${rows}
            </section>
        </div>`;
    }

    // ── Main render ───────────────────────────────────────────────────────────

    function render() {
        const body = $('featureModalBody');
        if (!body) return;

        if (state.mode === 'renderers') {
            body.innerHTML = renderRegisteredRenderers();
            return;
        }

        const cur      = state.data?.current || {};
        const rooms    = state.data?.rooms    || [];
        const standalone = state.data?.standalone_renderers || [];

        // Current space card
        const stream = cur.active_stream === 'hires' ? 'Hires' : 'Safe';
        let   curName = 'No space selected';
        if (cur.space_type === 'room')     curName = roomName(cur.space_id);
        if (cur.space_type === 'renderer') curName = rendererName(cur.space_id);

        const currentHtml = `
            <section class="spaces-current-card">
                <div class="spaces-eyebrow">Current Listening Space</div>
                <div class="spaces-current-main"><div>
                    <div class="spaces-current-name">${esc(curName)}</div>
                    <div class="spaces-current-sub">${esc(stream)}</div>
                </div></div>
            </section>`;

        // Rooms
        const roomsHtml = rooms.length === 0
            ? `<section class="spaces-section">
                <div class="spaces-section-title">Rooms</div>
                <div class="spaces-empty">No rooms yet. Create one below.</div>
               </section>`
            : `<section class="spaces-section">
                <div class="spaces-section-title">Rooms</div>
                ${rooms.map(room => {
                    const members     = Array.isArray(room.members) ? room.members : [];
                    const memberNames = members.map(id => rendererName(id)).join(', ');
                    const activeStream = room.active_stream || 'safe';

                    const memberRows = members.length
                        ? members.map(id => `
                            <div class="spaces-member-row">
                                <span>${esc(rendererName(id))}</span>
                                <button class="spaces-link-button"
                                    data-action="remove-renderer"
                                    data-room-id="${esc(room.room_id)}"
                                    data-renderer-id="${esc(id)}">Remove</button>
                            </div>`).join('')
                        : '<div class="spaces-muted">No renderers in this room.</div>';

                    const addOptions = standalone.map(r =>
                        `<option value="${esc(r.renderer_id)}">${esc(r.renderer_name || r.hostname || r.renderer_id)}</option>`
                    ).join('');

                    return `<article class="spaces-card">
                        <div class="spaces-card-header">
                            <div class="spaces-card-identity">
                                <div class="spaces-card-title">${esc(room.room_name)}</div>
                                <div class="spaces-card-sub">${esc(memberNames || 'Empty room')}</div>
                            </div>
                            <div class="spaces-card-controls">
                                ${streamPills('room', room.room_id, activeStream)}
                                <button class="spaces-delete-button"
                                    data-action="delete-room"
                                    data-room-id="${esc(room.room_id)}"
                                    data-room-name="${esc(room.room_name)}"
                                    title="Delete room" aria-label="Delete ${esc(room.room_name)}">&times;</button>
                            </div>
                        </div>
                        <div class="spaces-member-list">${memberRows}</div>
                        <div class="spaces-add-row">
                            <select class="spaces-select" data-add-select="${esc(room.room_id)}"
                                ${standalone.length === 0 ? 'disabled' : ''}>
                                ${standalone.length === 0
                                    ? '<option>No standalone renderers available</option>'
                                    : addOptions}
                            </select>
                            <button class="spaces-secondary-button"
                                data-action="add-renderer-from-select"
                                data-room-id="${esc(room.room_id)}"
                                ${standalone.length === 0 ? 'disabled' : ''}>Add</button>
                        </div>
                    </article>`;
                }).join('')}
               </section>`;

        // Standalone renderers
        const standaloneHtml = standalone.length === 0
            ? `<section class="spaces-section">
                <div class="spaces-section-title">Standalone Renderers</div>
                <div class="spaces-empty">All renderers are assigned to rooms.</div>
               </section>`
            : `<section class="spaces-section">
                <div class="spaces-section-title">Standalone Renderers</div>
                ${standalone.map(r => {
                    const isActive     = cur.space_type === 'renderer' && cur.space_id === r.renderer_id;
                    const activeStream = isActive ? (cur.active_stream || 'safe') : 'safe';
                    return `<article class="spaces-renderer-row">
                        <div>
                            <div class="spaces-card-title">${esc(r.renderer_name || r.hostname || r.renderer_id)}</div>
                            <div class="spaces-card-sub">${esc(r.model || r.ip_address || '')}</div>
                        </div>
                        ${streamPills('renderer', r.renderer_id, activeStream)}
                    </article>`;
                }).join('')}
               </section>`;

        // Create room
        const createRoomHtml = `
            <section class="spaces-section spaces-create-room">
                <div class="spaces-section-title">Create Room</div>
                <div class="spaces-create-form">
                    <input id="spacesRoomNameInput" class="spaces-input" type="text"
                        autocomplete="off" placeholder="Room name">
                    <button class="spaces-primary-button" data-action="create-room">Create</button>
                </div>
            </section>`;

        body.innerHTML = `
            <div class="spaces-panel">
                ${currentHtml}
                <div id="spacesStatus" class="spaces-status"></div>
                ${roomsHtml}
                ${standaloneHtml}
                ${createRoomHtml}
            </div>`;
    }

    // ── Action runner ─────────────────────────────────────────────────────────

    // Full run: calls action + re-fetches list. Use for structural changes.
    async function run(action, params = {}, successMsg = '') {
        try {
            setBusy(true);
            setStatus('Updating\u2026');
            await callApi(action, params);
            await loadData();
            updatePlayerContext();
            render();
            setStatus(successMsg || 'Done', 'ok');
        } catch (err) {
            console.error('[GeeSpaces]', action, err);
            setStatus(err.message || 'Action failed', 'error');
        } finally {
            setBusy(false);
        }
    }

    // Fast select: calls action, updates state from response directly, no re-fetch.
    // UI pill is already updated optimistically before this runs.
    async function runSelect(action, params, btn) {
        try {
            setBusy(true);
            const response = await callApi(action, params);

            // Update just the current pointer in state — no full list re-fetch.
            if (response.current && state.data) {
                state.data.current = response.current;

                // Also update the room's active_stream in state so re-renders are consistent.
                if (action === 'select-room' && params.room_id && state.data.rooms) {
                    state.data.rooms = state.data.rooms.map(r =>
                        r.room_id === params.room_id
                            ? { ...r, active_stream: params.stream }
                            : r
                    );
                }
            }

            updatePlayerContext();
            // Re-render just the current card to reflect confirmed state.
            // Pills are already correct from the optimistic update.
            const currentCard = $('featureModalBody')?.querySelector('.spaces-current-card');
            if (currentCard && state.data) {
                const cur    = state.data.current || {};
                const stream = cur.active_stream === 'hires' ? 'Hires' : 'Safe';
                let   name   = 'No space selected';
                if (cur.space_type === 'room')     name = roomName(cur.space_id);
                if (cur.space_type === 'renderer') name = rendererName(cur.space_id);
                currentCard.querySelector('.spaces-current-name').textContent = name;
                currentCard.querySelector('.spaces-current-sub').textContent  = stream;
            }

            setStatus('');
        } catch (err) {
            console.error('[GeeSpaces]', action, err);
            // Revert the optimistic pill change.
            if (btn) revertOptimisticPill(btn);
            setStatus(err.message || 'Could not switch — please try again.', 'error');
        } finally {
            setBusy(false);
        }
    }

    // ── Public: open ──────────────────────────────────────────────────────────

    async function open(mode = 'multiroom') {
        openModal(mode);
        try {
            await loadData();
            updatePlayerContext();
            render();
        } catch (err) {
            const body = $('featureModalBody');
            if (body) body.innerHTML = `<div class="spaces-empty spaces-error">${esc(err.message || 'Failed to load listening spaces')}</div>`;
        }
    }

    // ── Click handler ─────────────────────────────────────────────────────────

    async function handleClick(e) {
        // Close
        if (e.target.closest('#featureModalClose') || e.target.id === 'featureModalBackdrop') {
            closeModal();
            return;
        }

        const body = $('featureModalBody');
        const btn  = e.target.closest('[data-action]');
        if (!btn || !body?.contains(btn) || state.mode !== 'multiroom') return;

        e.preventDefault();

        const action = btn.dataset.action;
        if (state.busy) return;

        switch (action) {

            // ── Fast optimistic selects ───────────────────────────────────────
            case 'select-room':
                applyOptimisticPill(btn);
                await runSelect('select-room', {
                    room_id: btn.dataset.roomId,
                    stream:  btn.dataset.stream,
                }, btn);
                break;

            case 'select-renderer':
                applyOptimisticPill(btn);
                await runSelect('select-renderer', {
                    renderer_id: btn.dataset.rendererId,
                    stream:      btn.dataset.stream,
                }, btn);
                break;

            // ── Structural changes (full re-fetch) ────────────────────────────
            case 'remove-renderer':
                await run('remove-renderer', {
                    room_id:     btn.dataset.roomId,
                    renderer_id: btn.dataset.rendererId,
                }, 'Renderer removed');
                break;

            case 'add-renderer-from-select': {
                const roomId     = btn.dataset.roomId;
                const sel        = body.querySelector(`[data-add-select="${CSS.escape(roomId)}"]`);
                const rendererId = sel?.value || '';
                if (!rendererId) { setStatus('Choose a renderer first', 'error'); return; }
                await run('add-renderer', { room_id: roomId, renderer_id: rendererId }, 'Renderer added');
                break;
            }

            case 'delete-room': {
                const name = btn.dataset.roomName || btn.dataset.roomId;
                // Custom dark confirm — no jarring white browser dialog.
                const confirmed = await showConfirm(`Delete "${name}"? Renderers will become standalone.`);
                if (!confirmed) return;
                await run('delete-room', { room_id: btn.dataset.roomId }, 'Room deleted');
                break;
            }

            case 'create-room': {
                const input = $('spacesRoomNameInput');
                const name  = (input?.value || '').trim();
                if (!name) { setStatus('Enter a room name', 'error'); return; }
                await run('create-room', { room_name: name }, 'Room created');
                if (input) input.value = '';
                break;
            }
        }
    }

    // ── Init ──────────────────────────────────────────────────────────────────

    function init() {
        document.addEventListener('click', handleClick);

        // Background context refresh while modal is closed.
        async function refreshContext() {
            try {
                const data = await callApi('list');
                state.data = data;
                updatePlayerContext();
            } catch (_) { /* silent */ }
        }

        refreshContext();
        setInterval(refreshContext, 5000);
    }

    return { init, open };

})();

document.addEventListener('DOMContentLoaded', () => GeeSpaces.init());
