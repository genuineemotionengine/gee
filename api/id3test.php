<?php

require_once('/var/www/html/api/getid3.php'); 

$flacfile = "/test/id3/Roy Ayers - A Shining Symbol/01 - Running Away - Roy Ayers.flac";

//$flacfile = "/test/id3/Weezer - Hash Pipe/01 Weezer - Hash Pipe.flac";

$getID3 = new GetID3;

$ThisFileInfo = $getID3->analyze($flacfile);

//echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
print_r($ThisFileInfo, true);