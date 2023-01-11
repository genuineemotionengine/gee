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

echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$timestamp = date("YmdHis");
   


//$subdir = "/mnt/usb/".$dirarray[$x]."/";
//
//$subdirarray = scandir($subdir);
//
//echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
//
//$subelements = count($subdirarray);
//
//for ($y = 2; $y < $subelements; $y++) {
//    
//    include('/var/www/html/api/random.php');
//    
//    rename("/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/usb/".$dirarray[$x]."/".$random.".flac");
//    
//    echo "/mnt/usb/".$dirarray[$x]."/".$random.".flac\n";
//    
//}    
//    
//    
//    
//    
//    
//    
//    
//include('/var/www/html/api/random.php'); 

rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$timestamp);

echo "/mnt/usb/".$dirarray[$x]." renamed to /mnt/usb/".$timestamp."\n";

sleep(1);

    
}
    