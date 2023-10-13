<?php

require_once('mpd.class.php');

$mpd = mpd('localhost', 6600);

$mpdarray = $mpd->get_version();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

