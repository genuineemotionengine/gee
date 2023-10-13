<?php

require_once('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$mpdarray = $mpd->server_status();

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

