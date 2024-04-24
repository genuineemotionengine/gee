<?php

//$dir = "/mnt/usb/";
//
//$dirarray = scandir($dir);
//
//echo htmlentities(print_r($dirarray[0], true), ENT_SUBSTITUTE)."\n";
//
//echo htmlentities(print_r($dirarray[1], true), ENT_SUBSTITUTE)."\n";
//
//echo htmlentities(print_r($dirarray[2], true), ENT_SUBSTITUTE)."\n";
//
//if ($dirarray[2] >= 200000){
//    $count = 100000;
//} else {
//    $count = 200000; 
//}
//
//echo $count;


    if (function_exists("fread")) {
            echo "You have fread\n";
    } else {
            echo "You don't have fread\n";
    }