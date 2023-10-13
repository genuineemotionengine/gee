<?php

require_once('mpd.class.php');

$mpd = new mpd(NULL, 0, 0);

$mpdarray = $mpd->get_connection_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

