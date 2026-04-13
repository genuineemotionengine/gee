<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gee Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="/css/gee.css">
</head>
<body>

<div id="player">

    <div id="artwork">
        <img id="cover" src="/img/default.png" alt="Artwork">
    </div>

    <div id="meta">
        <div id="title">Loading...</div>
        <div id="artist"></div>
        <div id="album"></div>
    </div>

    <div id="controls">
        <button onclick="sendCommand(3)">Prev</button>
        <button onclick="sendCommand(2)">Play/Pause</button>
        <button onclick="sendCommand(4)">Next</button>
    </div>

</div>

<script>
const API_URL = '/api/?service=1';

async function fetchMeta() {
    try {
        const res = await fetch(API_URL);
        const data = await res.json();

        updateUI(data);

    } catch (e) {
        console.error('Fetch error:', e);
    }
}

function updateUI(data) {
    if (!data) return;

    document.getElementById('title').innerText = data.title || '';
    document.getElementById('artist').innerText = data.artist || '';
    document.getElementById('album').innerText = data.album || '';

    if (data.cover) {
        document.getElementById('cover').src = data.cover;
    }
}

async function sendCommand(service) {
    try {
        await fetch(`/api/?service=${service}`);
        fetchMeta();
    } catch (e) {
        console.error('Command error:', e);
    }
}

// Poll every 5 seconds
setInterval(fetchMeta, 5000);

// Initial load
fetchMeta();
</script>

</body>
</html>