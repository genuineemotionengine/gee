<?php
//$count = 100000;

$a = 1;

$dir = "/mnt/test/";

$dirarray = scandir($dir);

if ($dirarray[2] >= 200000){
    $count = 100000;
} else {
    $count = 200000; 
}

echo $count;

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 2; $x < $elements; $x++) {
    
$subdir = "/mnt/test/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {
    
    rename("/mnt/test/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/test/".$dirarray[$x]."/".$count.".flac");
    
    //echo "/mnt/test/".$dirarray[$x]."/".$subdirarray[$y]." renamed to /mnt/test/".$dirarray[$x]."/".$count.".flac\n";
    
    $count++;
      
}    
    
rename("/mnt/test/".$dirarray[$x],"/mnt/test/".$count);

echo $a." - /mnt/test/".$dirarray[$x]." renamed to /mnt/test/".$count."\n";

//echo $x." done\n";
$count++;
$a++;
}
    