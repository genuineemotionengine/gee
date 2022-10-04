<?php
parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];

require('mpd.class.php');
require_once('getid3.php');

$mpd = new mpd('localhost', 6600);

switch ($service){

case "1": //***************** Track Data **********************
  
$statusarray = $mpd->server_status();
    
$elapsed = $statusarray['elapsed'];


$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

$duration = $statusarray['duration'];

$durations = explode(".",$duration);



$refresh = $durations[0] - $elapsed;


$mySimpleArray = $mpd->current_song();
    
    //print_r($mySimpleArray);
      
$flacfile = $mySimpleArray[0]['name'];

$album = $mySimpleArray[0]['Album'];

$artist = $mySimpleArray[0]['Artist'];

$title = $mySimpleArray[0]['Title'];

$flacfile = "/mnt/usb/".$flacfile;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';

if(isset($ThisFileInfo['comments']['picture'][0])){
    $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
}

$rows = array(

'image' => $image,
'title' => $title,
'artist' => $artist,
'album' => $album,
'elapsed' => $elapsed,
'refresh' => $refresh
     
);

echo json_encode($rows);

break;

case "2": //***************** Pause **********************

   

$mpd->pause($pause);
    
 
    
header("Location: http://". $ipaddr ."");

    
break;

case "3": //***************** Previous **********************
    
$mpd->prev();
    
 
    
header("Location: http://". $ipaddr ."");
    
break;

case "4": //***************** Next **********************
    
$mpd->next();
    
 
    
header("Location: http://". $ipaddr ."");
    
break;

case "5": //***************** Restart Playlist **********************
    
$mpd->playlist_clear();
    
if ($playl == 1){    

$playlist = "allmusic";

}

if ($playl == 2){    

$playlist = "classical";

}


if ($playl == 3){    

$playlist = "relaxation";

}

    
$mpd->load_playlist($playlist);

$mpd->playlist_shuffle();

$mpd->play(0);
 
    
header("Location: http://". $ipaddr ."");
    
break;

case "6": //***************** get ip address **********************
    
$ipaddr = $_SERVER['SERVER_ADDR'];
    
echo $ipaddr."<br>";

$hosty = gethostname();

echo $hosty."<br>";

    
break;

default: //***************** Nothing **********************
    
    echo "...nothing";
    
}

