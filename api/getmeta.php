<?php

$statusarray = $mphpd->status();

//if ($verbose){
//echo "Status";
//echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>'; 
//echo "<br><br><br>";    
//}
//    
$elapsed = $statusarray['elapsed'];

$volume = $statusarray['volume'];

$state = $statusarray['state'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

$duration = $statusarray['duration'];

$durations = explode(".",$duration);

$refresh = $durations[0] - $elapsed;

$mySimpleArray = $mphpd->player()->current_song();
    
//if ($verbose){
//echo "Current Song";
//echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
//echo "<br><br><br>";    
//}
//      
$flacfile = $mySimpleArray['file'];

$album = $mySimpleArray['album'];

$artist = $mySimpleArray['artist'];

$title = $mySimpleArray['title'];

$albumartist = $mySimpleArray['albumartist'];

if (stripos("$albumartist, Various Artists - ", "Various Artists - ") === 0){
    $albumartist = "Various Artists";
}

$flacfile = "/mnt/usb/".$flacfile;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';

if(isset($ThisFileInfo['comments']['picture'][0])){
    $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
}


//$command = 'mpc queued';
//exec($command, $output);
////echo '<pre>'.htmlentities(print_r($output, true), ENT_SUBSTITUTE).'</pre>';
//
//$nextsong = explode(" - ",$output[0]);
//
//$nexttitle = ltrim($nextsong[1]);
//
//$nextartist = rtrim($nextsong[0]);

//echo "'".$nextartist."'"."<br><br>";
//
//echo "'".$nexttitle."'"."<br><br>";

$rows = ['image' => $image,
'title' => $title,
'artist' => $artist,
'album' => $album,
'elapsed' => $elapsed,
'duration' => $durations[0],
'albumartist' => $albumartist,
'volume' => $volume,
'nexttitle' => $nexttitle,
'nextartist' => $nextartist,    
'state' => $state
     ];


echo json_encode($rows);
