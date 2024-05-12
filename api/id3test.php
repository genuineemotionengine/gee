<?php

require_once('/var/www/html/api/id3/getid3.php'); 

$getID3 = new getID3;

$dir = "/mnt/test/";

$dirarray = scandir($dir);

$x = 2;
    
$subdir = "/mnt/test/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$y = 6;
    
$flacfile = "/mnt/test/".$dirarray[$x]."/".$subdirarray[$y];

echo $flacfile."\n";

$ThisFileInfo = $getID3->analyze($flacfile);

echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
