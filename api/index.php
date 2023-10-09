<?php

parse_str($_SERVER['QUERY_STRING'], $qsarray);

$service = $qsarray['service'];
//$id = $qsarray['id'];
//$mod = $qsarray['mod'];

$ipaddr = $_SERVER['SERVER_ADDR'];

require_once('dbconn.php');

require_once('mpd.class.php');

require_once('getid3.php');   

$mpd = new mpd('localhost', 6600);

if ($mpd === true) {
  // connection successful
} else {
  echo $mpd->get_error();
}

//***************** Just Get Meta **********************

if ($service == 1){ 
    
    include ('getmeta.php');

}


    
//***************** Next **********************

if ($service == 4){ 
    
    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];
        
    $mpd->next();
    
    if ($state === 'play'){
        $pause = 0;
    }

    if ($state === 'pause'){
        $pause = 1;
    }

    $mpd->pause($pause);    
    
    include ('getmeta.php');

}



//***************** Pause **********************

if ($service == 2){

    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];

    if ($state == 'play'){
        $pause = 1;
    }

    if ($state == 'pause'){
        $pause = 0;
    }

    $mpd->pause($pause);

    include ('getmeta.php');
    
}

//***************** Previous **********************

if ($service == 3){
    
    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];
       
    $mpd->prev();
    
    if ($state === 'play'){
        $pause = 0;
    }

    if ($state === 'pause'){
        $pause = 1;
    }

    $mpd->pause($pause);

    include ('getmeta.php');

}

//***************** Restart Playlist **********************

if ($service == 5){
    
$sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";    

include ('loadplaylist.php');
   
include ('getmeta.php');
 
}

//***************** load classical playlist **********************

if ($service == 6){  

$sql = "SELECT albumpath FROM app WHERE genre = 'Classical'";
    
include ('loadplaylist.php');
   
include ('getmeta.php');
   
}

//***************** load relaxation playlist **********************

if ($service == 7){
    
$sql = "SELECT albumpath FROM app WHERE genre = 'Relaxation' or genre = 'Ambient' or genre = 'Chilled Electronic'";    

include ('loadplaylist.php');
   
include ('getmeta.php');

}


//***************** get album list **********************

if ($service == 8){

include ('getalbum.php'); 
    
echo json_encode($albumtracks);

}

//****************** Play Next ***************

if ($service == 12){

$mySimpleArray = $mpd->current_song();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

$sql = "SELECT albumpath FROM app WHERE id = '".$id."'";
if ($verbose){
echo "sql: ".$sql."<br>";
}
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       
        $uri = $row['albumpath'];

       }
     } 

   

$pos = $mySimpleArray[0]['Pos'];

$pos++;

if ($verbose){
echo "uri: ".$uri."<br>";

echo "pos: ".$pos."<br>";
}


$results = $mpd->playlist_add_id($uri, $pos);

if ($plnext){
    $mpd->next();
    
}

if ($verbose){

echo '<pre>'.htmlentities(print_r($results, true), ENT_SUBSTITUTE).'</pre>';

}



}


//***************** set vol + **********************

if ($service == 9){  
    


$mpd->setvol($vol);





    
}

//***************** set vol - **********************

if ($service == 15){  
    


$mpd->adjust_vol($mod);


$statusarray = $mpd->server_status();

$volume = $statusarray['volume'];

$rows = ['volume' => $volume];


echo json_encode($rows);
    
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
    
$currentArray = $mpd->current_song(); 
  
$playlist = "app";

$pos = $statusarray['song'];

$pos = $currentArray[0]['Pos'];

$pos++;

//$range = "0:";

echo "pos: ".$pos."<br>";

$loadarray = $mpd->load_next($playlist, $pos);

echo '<pre>'.htmlentities(print_r($loadarray, true), ENT_SUBSTITUTE).'</pre>';


}





//****************** Build Database ***************

if ($service == 14){

//$sql = "DROP TABLE allmusic";
//$result = $conn->query($sql);
//echo mysqli_error($conn)."<br>";
//
//$sql = "CREATE TABLE allmusic (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//albumpath varchar(512),
//title varchar(512),
//artist varchar(512),
//album varchar(512),
//albumartist varchar(512)
//)";
//
//$result = $conn->query($sql);
//echo mysqli_error($conn)."<br>";


    
$dir = "/mnt/usb/";


$dirarray = scandir($dir);

$elements = count($dirarray);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

for ($x = 3; $x < $elements; $x++) {

echo $dirarray[$x]."<br>";



$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {

//echo $dirarray[$x]."/".$subdirarray[$y]."<br>";

$name = $dirarray[$x]."/".$subdirarray[$y];

echo $name."<br>";

$flacfile = "/mnt/usb/".$name;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

$title = $ThisFileInfo['tags']['id3v2']['title'][0];

echo "Title: ".$title."<br>";

$artist = $ThisFileInfo['tags']['id3v2']['artist'][0];

echo "Artist: ".$artist."<br>";

$album = $ThisFileInfo['tags']['id3v2']['album'][0];

echo "Album: ".$album."<br>";

$albumartist = $ThisFileInfo['tags']['id3v2']['band'][0];

echo "Album Artist: ".$albumartist."<br>";

$name =  str_replace("'","&#39;",$name);
$title =  str_replace("'","&#39;",$title);
$artist =  str_replace("'","&#39;",$artist);
$album =  str_replace("'","&#39;",$album);
$albumartist =  str_replace("'","&#39;",$albumartist);


$sql="INSERT INTO allmusic (albumpath, title, artist, album, albumartist) VALUES ('$name', '$title', '$artist', '$album' '$albumartist')";

echo $sql."<br><br>\n"; 

//$conn->query($sql);
//
//echo mysqli_error($conn)."<br>";

//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['tags'], true), ENT_SUBSTITUTE).'</pre>';

}
echo $x."<br>";
}


//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';
   
    
//$subdir = "/mnt/usb/".$dirarray[4]."/";
//    
//$subdirarray = scandir($subdir);  
//
//echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    
}
//****************** time elapsed ********************

if ($service == 16){
    
$statusarray = $mpd->server_status();

//echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
    
$elapsed = $statusarray['elapsed'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

//echo $elapsed."<br><br><br>";

$rows = [
    
'elapsed' => $elapsed
        
];

echo json_encode($rows);

}

//***************** serch by track **********************

if ($service == 21){  

$sql="UPDATE searchterm SET term ='1'";
$conn->query($sql);
   if (mysqli_error($conn)){
            echo mysqli_error($conn)."<br/><br/>";
        }
}

//***************** serch by album **********************

if ($service == 22){  

$sql="UPDATE searchterm SET term ='2'";
$conn->query($sql);


}

//***************** serch by artist **********************

if ($service == 23){  

$sql="UPDATE searchterm SET term ='3'";
$conn->query($sql);


}

//****************** MPD Test **********************

if ($service == 24){  

$updatearray = $mpd->update_db();

echo '<pre>'.htmlentities(print_r($updatearray, true), ENT_SUBSTITUTE).'</pre>';
    
//echo exec('alsamixer') . " \n";    

}

//****************** Print Playlist **********************

if ($service == 25){  
    
$playlist = "app";    

$fullplaylist = $mpd->playlistinfo($playlist);

echo '<pre>'.htmlentities(print_r($fullplaylist, true), ENT_SUBSTITUTE).'</pre>';
    

}




//****************** Next Track working **********************

if ($service == 26){
    
//$playlist = "app";    
//
//$fullplaylist = $mpd->playlistinfo($playlist);

//$mpd->tagsall();
    
$tagsArray = $mpd->tags();

//$albumpath = $mySimpleArray[0]['name'];

if ($verbose){
echo "tags";
echo '<pre>'.htmlentities(print_r($tagsArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}


    
$statusArray = $mpd->server_status();

//$albumpath = $mySimpleArray[0]['name'];

if ($verbose){
echo "Status";
echo '<pre>'.htmlentities(print_r($statusArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

$currentArray = $mpd->current_song();

//$albumpath = $mySimpleArray[0]['name'];

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($currentArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

$type = "Title";

$what = "Flaphead";

echo "type: ".$type."<br>";

echo "what: ".$what."<br><br>";

$findArray = $mpd->find($type, $what);

//$albumpath = $mySimpleArray[0]['name'];

if ($verbose){
echo "Search";
echo '<pre>'.htmlentities(print_r($findArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}





//$sql = "SELECT * FROM app WHERE albumpath = '".$albumpath."'";
//if ($verbose){
//echo "sql: ".$sql."<br>";
//}
//$result = $conn->query($sql);
//if ($result->num_rows > 0) {
//    while($row = $result->fetch_assoc()) {
//       
//        $uri = $row['albumpath'];
//        $title = $row['title'];
//        $artist = $row['artist'];
//
//       }
//     } 
//
   

//$pos = $mySimpleArray[0]['Pos'];
//
//$pos++;
//
//if ($verbose){
//    
//echo "pos: ".$pos."<br>";    
//    
//$uri = $fullplaylist[$pos]['name'];
//$title = $fullplaylist[$pos]['Title'];  
//$artist = $fullplaylist[$pos]['Artist'];  
//    
//echo "uri: ".$uri."<br>";
//echo "title: ".$title."<br>";
//echo "artist: ".$artist."<br>";


//}


//if ($verbose){
//
//echo "Next Song";    
//echo '<pre>'.htmlentities(print_r($results, true), ENT_SUBSTITUTE).'</pre>';
//
//}

    

}