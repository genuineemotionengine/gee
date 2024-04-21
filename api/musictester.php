<?php



$dir = "/mnt/usb/";

$dirarray = scandir($dir);

echo htmlentities(print_r($dirarray[0], true), ENT_SUBSTITUTE)."\n";

echo htmlentities(print_r($dirarray[1], true), ENT_SUBSTITUTE)."\n";

echo htmlentities(print_r($dirarray[2], true), ENT_SUBSTITUTE)."\n";

$count = 100000;