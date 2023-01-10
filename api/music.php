<?php

require_once('/var/www/html/api/dbconn.php');

require_once('/var/www/html/api/getid3.php');

$dir = "/mnt/usb/";

$dirarray = scandir($dir);

echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 3; $x < 4; $x++) {

include('/var/www/html/api/random.php'); 

rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$random);
    
}
    