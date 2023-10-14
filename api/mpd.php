<?php

 include('config.php');

require_once('mpd.class.php');

$mpd = new mpd;

$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

