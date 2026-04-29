<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
<meta name="theme-color" content="#000000">
<link rel="icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="stylesheet" href="/css/gee.css?v=102">
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
                            <button type="button" class="zone" data-zone="refresh" title="Refresh">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="search" title="Search">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="load" title="Load Music">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M3.5 6a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 1 0-1h2A1.5 1.5 0 0 1 14 6.5v8a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-8A1.5 1.5 0 0 1 3.5 5h2a.5.5 0 0 1 0 1z"/><path fill-rule="evenodd" d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="multiroom" title="Multi Room">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h4a.5.5 0 1 0 0-1h-4a.5.5 0 0 1-.5-.5V7.207l5-5 6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293z"/><path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1h-1a.5.5 0 0 0 0 1h1v1a.5.5 0 1 0 1 0v-1h1a.5.5 0 1 0 0-1h-1v-1a.5.5 0 0 0-.5-.5"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="playpause" title="Play / Pause">
                                <span class="zone-icon zone-icon-play">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                                        <path d="M10.804 8 5 4.633v6.734zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696z"/>
                                    </svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="renderers" title="Renderers">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M8 4.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5M8 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4m0 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m-3.5 1.5a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="prev" title="Previous">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="restart" title="Restart Track">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.854 3.646a.5.5 0 0 1 0 .708L8.207 8l3.647 3.646a.5.5 0 0 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 0 1 .708 0M4.5 1a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 1 0v-13a.5.5 0 0 0-.5-.5"/></svg>
                                </span>
                            </button>

                            <button type="button" class="zone" data-zone="next" title="Next">
                                <span class="zone-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/></svg>
                                </span>
                            </button>
                        </div>                        
                        

                        <div id="gridHelper" class="grid-helper" aria-hidden="true">
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                            <div class="grid-helper-cell"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="player-body">
                <div class="context-line">
                    <span id="stream" class="context-stream context-stream-left">--</span>

                    <div class="context-meta-line">
                        <span id="renderer" class="context-renderer">Loading…</span>
                    </div>

                    <button
                        type="button"
                        id="gridHelperToggle"
                        class="context-eye-button"
                        aria-label="Show navigation grid"
                        title="Show navigation grid"
                    >
                        <span class="eye-icon eye-off" aria-hidden="false">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z"/>
                                <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z"/>
                            </svg>
                        </span>

                        <span class="eye-icon eye-on" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/>
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="progress-wrap">
                    <div class="progress-row">
                        <span id="elapsed" class="time-value">0:00</span>

                        <div class="track-bar" role="progressbar" aria-label="Track progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div id="progressFill" class="track-fill"></div>
                        </div>

                        <span id="duration" class="time-value">0:00</span>
                    </div>
                </div>

                <div class="transport-panel">
                    <div class="volume-panel">
                        <div class="volume-bar-wrap">
                            <button type="button" class="volume-step" id="volDownButton" title="Volume down" aria-label="Volume down">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M9 4a.5.5 0 0 0-.812-.39L5.825 5.5H3.5A.5.5 0 0 0 3 6v4a.5.5 0 0 0 .5.5h2.325l2.363 1.89A.5.5 0 0 0 9 12zM6.312 6.39 8 5.04v5.92L6.312 9.61A.5.5 0 0 0 6 9.5H4v-3h2a.5.5 0 0 0 .312-.11M12.025 8a4.5 4.5 0 0 1-1.318 3.182L10 10.475A3.5 3.5 0 0 0 11.025 8 3.5 3.5 0 0 0 10 5.525l.707-.707A4.5 4.5 0 0 1 12.025 8"/>
                                </svg>
                            </button>

                            <div id="volumeBar" class="volume-bar" role="progressbar" aria-label="Volume" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div id="volumeFill" class="volume-fill"></div>
                            </div>

                            <button type="button" class="volume-step" id="volUpButton" title="Volume up" aria-label="Volume up">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M11.536 14.01A8.47 8.47 0 0 0 14.026 8a8.47 8.47 0 0 0-2.49-6.01l-.708.707A7.48 7.48 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303z"/>
                                    <path d="M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.48 5.48 0 0 1 11.025 8a5.48 5.48 0 0 1-1.61 3.89z"/>
                                    <path d="M10.025 8a4.5 4.5 0 0 1-1.318 3.182L8 10.475A3.5 3.5 0 0 0 9.025 8c0-.966-.392-1.841-1.025-2.475l.707-.707A4.5 4.5 0 0 1 10.025 8M7 4a.5.5 0 0 0-.812-.39L3.825 5.5H1.5A.5.5 0 0 0 1 6v4a.5.5 0 0 0 .5.5h2.325l2.363 1.89A.5.5 0 0 0 7 12zM4.312 6.39 6 5.04v5.92L4.312 9.61A.5.5 0 0 0 4 9.5H2v-3h2a.5.5 0 0 0 .312-.11"/>
                                </svg>
                            </button>
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

<script src="/js/gee-player.js?v=20260420n"></script>
</body>
</html>