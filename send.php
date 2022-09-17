<?php
require('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$mpd = playlist_clear;
echo "done";