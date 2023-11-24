<?php

require_once('/var/www/html/api/getid3.php'); 

//$flacfile = "/test/id3/Wes Montgomery - Full House/01 - Full House - Wes Montgomery.flac";

$flacfile = "/test/id3/Weezer - Hash Pipe/01 Weezer - Hash Pipe.flac";

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
