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

//echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
    
$elapsed = $statusarray['elapsed'];

$state = $statusarray['state'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

$duration = $statusarray['duration'];

$durations = explode(".",$duration);



$refresh = $durations[0] - $elapsed;


$mySimpleArray = $mpd->current_song();
    
    //print_r($mySimpleArray);
    //echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>';
      
$flacfile = $mySimpleArray[0]['name'];

$album = $mySimpleArray[0]['Album'];

$artist = $mySimpleArray[0]['Artist'];

$title = $mySimpleArray[0]['Title'];

$albumartist = $mySimpleArray[0]['AlbumArtist'];

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

//$mpd->next();
//
//$nextsong = $mpd->current_song();
//
//$nexttitle = $nextsong[0]['Title'];
//
//$nextartist = $nextsong[0]['Artist'];
//
//$mpd->prev();


$rows = ['image' => $image,
'title' => $title,
'artist' => $artist,
'album' => $album,
'elapsed' => $elapsed,
'duration' => $durations[0],
'albumartist' => $albumartist,
//'nexttitle' => $nexttitle,
//'nextartist' => $nextartist,    
'state' => $state
     ];

echo json_encode($rows);
}

//***************** Pause **********************

$statusarray = $mpd->server_status();

$state = $statusarray['state'];

if ($service == 2){
    
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

$playlist = "app";

}

if ($playl == 2){    

$playlist = "classical";

}


if ($playl == 3){    

$playlist = "relaxation";

}

    
$mpd->load_playlist($playlist);

//$mpd->playlist_shuffle();

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


//***************** get albums **********************

if ($service == 7){  

$playlist = "allmusic";


$albumarray = $mpd->playlistinfo($playlist);



echo '<pre>'.htmlentities(print_r($albumarray, true), ENT_SUBSTITUTE).'</pre>';  

    
}

//***************** get album list **********************

if ($service == 8){


    
$statusarray = $mpd->server_status(); 

if ($verbose){
echo "Server Status";
echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";    
}

$statsarray = $mpd->server_stats();

if ($verbose){
echo "Server Stats";
echo '<pre>'.htmlentities(print_r($statsarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";     
}
$mySimpleArray = $mpd->current_song();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

$what = $mySimpleArray[0]['Album'];

$type = "Album";

$playlistarray = $mpd->search($type, $what);



$elements = count($playlistarray);

if ($verbose){
echo "Album Search Results<br>";
echo "No of Elements: ".$elements;
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";
}





for ($x = 0; $x <= $elements; $x++) {
  if ($what != $playlistarray[$x][Album]){
      unset($playlistarray[$x]);
  }
}
if ($verbose){
echo "Album Search Results With Element Removed<br>";
echo "No of Elements: ".count($playlistarray);
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";
}

//if ($playnow == 1){
//    $elements = count($playlistarray);
//  for ($x = 0; $x <= $elements; $x++) {
//  if ($track != $playlistarray[$x][Track]){
//      unset($playlistarray[$x]);
//  }else{
//      $t = $x;
//  }
//}
//echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
//
//$uri = $playlistarray[$t]['name'];
//
//echo $uri;
//
////$mpd->playlist_add($uri);
//    
//    
//}else{


echo json_encode($playlistarray);


}
//***************** set vol **********************

if ($service == 9){  
    


$mpd->setvol($vol);

header("Location: http://". $ipaddr ."");



    
}

//***************** serach **********************

if ($service == 10){  
    
    


$what = $title;

echo $title."<br>";

$type = "Title";

$playlistarray = $mpd->search($type, $what);

$elements = count($playlistarray);

echo "Album Search Results<br>";
echo "No of Elements: ".$elements;
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";





for ($x = 0; $x <= $elements; $x++) {
  if ($album != $playlistarray[$x][Album]){
      unset($playlistarray[$x]);
  }
}



echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
    
}
    


//****************** Up Next ***************

if ($service == 11){
  
    
//$type = "Album";
//
//$what = "x";
    
$tagarray = $mpd->tags();      

echo '<pre>'.htmlentities(print_r($tagarray, true), ENT_SUBSTITUTE).'</pre>';


}

