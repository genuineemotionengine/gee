<?php

require('mpd.class.php');

//$mpd = new mpd('localhost', 6600);

$mpd = new mpd(NULL, 0, 0);



$mpdarray = $mpd->get_error();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

