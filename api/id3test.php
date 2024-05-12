<?php

require_once('/var/www/html/api/id3/getid3.php'); 

$dir = "/mnt/test/";

$dirarray = scandir($dir);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

//for ($x = 2; $x < $elements; $x++) {

$x = 2;
    
$subdir = "/mnt/test/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

//for ($y = 2; $y < $subelements; $y++) {

$y = 6;
    
    //rename("/mnt/test/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/test/".$dirarray[$x]."/".$count.".flac");
    
    $flacfile = "/mnt/test/".$dirarray[$x]."/".$subdirarray[$y];


$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
