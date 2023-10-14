<?php

require_once('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$playlist = "app";

$mpd->load_playlist($playlist);

$mpd->play(0);

$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

