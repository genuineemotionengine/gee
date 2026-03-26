<?php
//$count = 100000;

$a = 1;

$dir = "/mnt/music/";

$dirarray = scandir($dir);

if ($dirarray[2] >= 200000){
    $count = 100000;
} else {
    $count = 200000; 
}

//$count = 300000;

echo $count."\n";

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$subdir = "/mnt/music/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {
    
    rename("/mnt/music/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/music/".$dirarray[$x]."/".$count.".flac");
    
    //echo "/mnt/music/".$dirarray[$x]."/".$subdirarray[$y]." renamed to /mnt/music/".$dirarray[$x]."/".$count.".flac\n";
    
    $count++;
      
}    
    
rename("/mnt/music/".$dirarray[$x],"/mnt/music/".$count);

echo $a." - /mnt/music/".$dirarray[$x]." renamed to /mnt/music/".$count."\n";

//echo $x." done\n";
$count++;
$a++;
}
    