<?php

require_once('/var/www/html/api/id3/getid3.php'); 

$flacfile = "/mnt/test/Tasmin Archer - Great Expectations/01 Tasmin Archer - Sleeping Satellite.flac";

//$flacfile = "/test/id3/Weezer - Hash Pipe/01 Weezer - Hash Pipe.flac";

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
