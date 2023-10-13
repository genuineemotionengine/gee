<?php

require_once('mpd.class.php');

$mpdarray = $mpd->server_status();

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

