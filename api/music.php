<?php
$count = 200000;

$dir = "/mnt/swp/";

$dirarray = scandir($dir);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$subdir = "/mnt/swp/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {
    
    rename("/mnt/swp/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/swp/".$dirarray[$x]."/".$count.".flac");
    
    echo "/mnt/swp/".$dirarray[$x]."/".$subdirarray[$y]." renamed to /mnt/swp/".$dirarray[$x]."/".$count.".flac\n";
    
    $count++;
      
}    
    
rename("/mnt/swp/".$dirarray[$x],"/mnt/swp/".$count);

echo "/mnt/swp/".$dirarray[$x]." renamed to /mnt/swp/".$count."\n";

echo $x." done\n";

$count++;
}
    