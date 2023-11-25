<?php

require_once('/var/www/html/api/id3/GetID3.php'); 

$flacfile = "/test/id3/Wes Montgomery - Full House/05 - Round Midnight - Wes Montgomery.flac";

//$flacfile = "/test/id3/Weezer - Hash Pipe/01 Weezer - Hash Pipe.flac";

$getID3 = new GetID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
