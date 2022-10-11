<?php
parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];

require('mpd.class.php');
require_once('getid3.php');

$mpd = new mpd('localhost', 6600);

//switch ($service){

//***************** Track Data **********************

if ($service == 1){
  
$statusarray = $mpd->server_status();
    
$elapsed = $statusarray['elapsed'];

$state = $statusarray['state'];

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

$rows = ['image' => $image,
'title' => $title,
'artist' => $artist,
'album' => $album,
'elapsed' => $elapsed,
'duration' => $durations[0],
'state' => $state
     ];

echo json_encode($rows);
}

//***************** Pause **********************

if ($service == 2){
    
    echo $state;
    
if ($state === 'play'){
    $pause = 1;
}

if ($state === 'pause'){
    $pause = 0;
}

   

$mpd->pause($pause);
    

    
//header("Location: http://". $ipaddr ."");

    
}

//***************** Previous **********************
if ($service == 3){ 
    
$mpd->prev();
    
}

//***************** Next **********************
if ($service == 4){ 
    
$mpd->next();
    
}

//***************** Restart Playlist **********************

if ($service == 5){ 
    
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
 
}

//***************** get ip address **********************

if ($service == 6){  
    
$ipaddr = $_SERVER['SERVER_ADDR'];
    
echo $ipaddr."<br>";

$hosty = gethostname();

echo $hosty."<br>";

    

    
}

