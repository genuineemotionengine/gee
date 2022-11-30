<?php

$http_origin = $_SERVER['HTTP_ORIGIN'];

if ($http_origin == "http://192.168.68.108:3000")
{  
    header("Access-Control-Allow-Origin: $http_origin");
}


parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];

include "dbconn.php";

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

//
//$rows = ['image' => $image,
//'title' => $title,
//'artist' => $artist,
//'album' => $album,
//'elapsed' => $elapsed,
//'duration' => $durations[0],
//'albumartist' => $albumartist,
////'nexttitle' => $nexttitle,
////'nextartist' => $nextartist,    
//'state' => $state
//     ];


$rows = array(
      
    // Ankit will act as key
    "currentalbum" => array(
          
        'title' => $title,
        'artist' => $artist,
        'album' => $album,
        'elapsed' => $elapsed,
        'duration' => $durations[0],
        'albumartist' => $albumartist,
        'state' => $state
    ),

);
 






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
    

    
header("Location: http://". $ipaddr ."");

    
}

//***************** Previous **********************
if ($service == 3){
    
header("Location: http://". $ipaddr ."");    
$mpd->prev();
    
}

//***************** Next **********************
if ($service == 4){ 
    
$mpd->next();
header("Location: http://". $ipaddr ."");    
}

//***************** Restart Playlist **********************

if ($service == 5){
    
   
$mpd->playlist_clear();
    
if ($playl == 1){    

$playlist = "app";
$count = 0;    

$sql = "SELECT * FROM allmusic";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $myalbum = $row['album'];
        
        $myalbum = str_replace("&#39;","'",$myalbum);
        
        $myalbum = $myalbum."\n";
        
        $myalbumarray[$count] = $myalbum;
        
        $count++;

       }
     } 
     

$elements = count($myalbumarray);

shuffle($myalbumarray);

$myfile = fopen("/mnt/usb/000Playlists/app.m3u", "w") or die("Unable to open file!");

for ($x = 0; $x < $elements; $x++) {

  fwrite($myfile, $myalbumarray[$x]);
    
}  
fclose($myfile);

$mpd->load_playlist($playlist);

$mpd->repeat(1);

$mpd->play(0);


}

if ($playl == 2){    

$playlist = "classical";

$mpd->load_playlist($playlist);

$mpd->playlist_shuffle();

$mpd->repeat(1);

$mpd->play(0);


}


if ($playl == 3){    

$playlist = "relaxation";

$mpd->load_playlist($playlist);

$mpd->playlist_shuffle();

$mpd->repeat(1);

$mpd->play(0);


}



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

if ($playnext == 1){
    $elements = count($playlistarray);
  for ($x = 0; $x <= $elements; $x++) {
  if ($name != $playlistarray[$x][name]){
      unset($playlistarray[$x]);
  }else{
      $t = $x;
  }
}
if ($verbose){
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
}
$uri = $playlistarray[$t]['name'];

//$uri = "mnt/usb/".$uri;

$pos = $statusarray['song'];

$pos++;

//$pos = "+".$pos;

if ($verbose){
echo "uri: ".$uri."<br>";

echo "pos: ".$pos."<br>";
}
$results = $mpd->playlist_add_id($uri, $pos);

if ($playnow == 1){
    $mpd->next();
    
}

header("Location: http://". $ipaddr ."");

echo '<pre>'.htmlentities(print_r($results, true), ENT_SUBSTITUTE).'</pre>';

}else{


echo json_encode($playlistarray);

}
}
//***************** set vol + **********************

if ($service == 9){  
    


$mpd->setvol($vol);

header("Location: http://". $ipaddr ."");



    
}

//***************** set vol - **********************

if ($service == 15){  
    


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
    
$statusarray = $mpd->server_status();    
  
$playlist = "relaxation";

$pos = $statusarray['song'];

$pos = "+".$pos;

//$range = "0:";

echo "pos: ".$pos."<br>";

$loadarray = $mpd->load_next($playlist, $pos);

echo '<pre>'.htmlentities(print_r($loadarray, true), ENT_SUBSTITUTE).'</pre>';


}


//****************** Insert Next Track ***************

if ($service == 12){
    
$uri = "Oasis - Living Fast/02 Oasis - She's Electric.flac";

$pos = "9";

$insertarray = $mpd->playlist_add_id($uri, $pos);

echo '<pre>'.htmlentities(print_r($insertarray, true), ENT_SUBSTITUTE).'</pre>';

}

//****************** Insert Next Track ***************

if ($service == 13){
    
$uri = "Oasis - Living Fast/02 Oasis - She's Electric.flac";

$pos = "4";

$insertarray = $mpd->playlist_add_id($uri, $pos);

echo '<pre>'.htmlentities(print_r($insertarray, true), ENT_SUBSTITUTE).'</pre>';

$mpd->next();

}

//****************** Build Database ***************

if ($service == 14){
    
$dir = "/mnt/usb/";

// Sort in ascending order - this is default
$dirarray = scandir($dir);



echo '<pre>'.htmlentities(print_r($dirarray[23], true), ENT_SUBSTITUTE).'</pre>';
   
    
$subdir = "/mnt/usb/".$dirarray[4]."/";
    
$subdirarray = scandir($subdir);  

echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    
}