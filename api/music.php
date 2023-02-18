<?php
$count = 200000;

$dir = "/mnt/usb/";

$dirarray = scandir($dir);

echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {
    
    rename("/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/usb/".$dirarray[$x]."/".$count.".flac");
    
    echo "/mnt/usb/".$dirarray[$x]."/".$subdirarray[$y]." renamed to /mnt/usb/".$dirarray[$x]."/".$count.".flac\n";
    
    $count++;
      
}    
    
rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$count);

echo "/mnt/usb/".$dirarray[$x]." renamed to /mnt/usb/".$count."\n";

echo $x." done\n";

$count++;
}
    