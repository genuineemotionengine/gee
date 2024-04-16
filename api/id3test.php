<?php

require_once('/var/www/html/api/id3/getid3.php'); 

$flacfile = "/mnt/usb/214063/214061.flac";

//$flacfile = "/test/id3/Weezer - Hash Pipe/01 Weezer - Hash Pipe.flac";

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
