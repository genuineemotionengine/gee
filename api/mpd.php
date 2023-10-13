<?php

require('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$mpdarray = $mpd->get_connection_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

