<?php

require_once('/var/www/html/api/dbconn.php');

require_once('/var/www/html/api/getid3.php');

$dir = "/mnt/usb/";

$dirarray = scandir($dir);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 3; $x < $elements; $x++) {

$subdir = "/mnt/usb/".$dirarray[$x]."/";

//$subdirarray = scandir($subdir);
//
//$subelements = count($subdirarray);
//
//for ($y = 2; $y < $subelements; $y++) {
//    
//    include('/var/www/html/api/random.php'); 
//        
//    rename("/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/usb/".$dirarray[$x]."/".$random);
//    
//    
//}    
//    
    
    
    
    
    
    
include('/var/www/html/api/random.php'); 

rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$random);
    
}
    