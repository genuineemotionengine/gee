<?php
//
//for ($x = 1; $x < 100; $x++) {
//
//$timestamp = date("YmdHis");
//        
//echo "Timestamp: ".$timestamp."\n";
//
//sleep(1);
//
//
//}

//require_once('/var/www/html/api/dbconn.php');

//require_once('/var/www/html/api/getid3.php');

$dir = "/mnt/usb/";

$dirarray = scandir($dir);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'\n';

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {
    
    $timestamp = date("YmdHis");
    
    rename("/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/usb/".$dirarray[$x]."/".$timestamp.".flac");
    
    echo "/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y]." renamed to /mnt/usb/".$dirarray[$x]."/".$timestamp.".flac\n";
    
    sleep(1);
      
}    
    
    
    
    
    
    
    
//include('/var/www/html/api/random.php'); 

//rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$timestamp);
//
//echo "/mnt/usb/".$dirarray[$x]." renamed to /mnt/usb/".$timestamp."\n";



    
}
    