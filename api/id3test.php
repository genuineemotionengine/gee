<?php

require_once('/var/www/html/api/getid3.php'); 

$flacfile = "/test/id3/Wes Montgomery - Full House/01 Wes Montgomery - Full House.flac";

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
