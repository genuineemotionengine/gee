<?php

$mySimpleArray = $mpd->current_song();

$albumtracks = array();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}


$sql = "SELECT idalbum FROM app WHERE albumpath = '".$mySimpleArray[0]['name']."'";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       
        $idalbum = $row['idalbum'];
        if ($verbose){
        echo "idalbum: ".$idalbum."<br><br>";
        }
       }
     } 

$count = 0;
$sql = "SELECT albumpath, title, artist, albumartist, track FROM app WHERE idalbum = '".$idalbum."'";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $albumpath = $row['albumpath'];
        $title = $row['title'];
        $artist = $row['artist'];
//        $albumartist = $row['albumartist'];
        $track = $row['track'];
        
        $title =  str_replace("&#39;","'",$title);
        $artist =  str_replace("&#39;","'",$artist);
//        $albumartist =  str_replace("&#39;","'",$albumartist);
        
        if ($verbose) {
        echo "albumpath: ".$albumpath."<br>";
        echo "title: ".$title."<br>";
        echo "artist: ".$artist."<br>";
//        echo "albumartist: ".$albumartist."<br>";
        echo "track: ".$track."<br><br>";
        }
  
        $albumtracks[$count] = array(
            "albumpath" => $albumpath, 
            "title" => $title, 
            "artist" => $artist,
//            "albumartist" => $albumartist,
            "track" => $track
        );        
        
       $count++;
        
       }
     } 

    if ($verbose) {
        echo '<pre>'.htmlentities(print_r($albumtracks, true), ENT_SUBSTITUTE).'</pre>';
    }

    echo json_encode($albumtracks);



//$statusarray = $mpd->server_status(); 
//
//if ($verbose){
//echo "Server Status";
//echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
//echo "<br><br><br>";    
//}

//$mySimpleArray = $mpd->current_song();
//
//if ($verbose){
//echo "Current Song";
//echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
//echo "<br><br><br>";    
//}
//
//$what = $mySimpleArray[0]['Album'];
//
//$type = "Album";
//
//$playlistarray = $mpd->search($type, $what);
//
//$elements = count($playlistarray);
//
//if ($verbose){
//echo "Album Search Results<br>";
//echo "No of Elements: ".$elements;
//echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
//echo "<br><br><br>";
//}
//
//for ($x = 0; $x <= $elements; $x++) {
//  if ($what != $playlistarray[$x][Album]){
//      unset($playlistarray[$x]);
//  }
//  
// 
//  
//}
//if ($verbose){
//echo "Album Search Results With Element Removed<br>";
//$elements = count($playlistarray);
//echo "No of Elements: ".$elements;
//echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
//echo "<br><br><br>";
//}
//
//$elements--;
//for ($x = 0; $x <= $elements; $x++) {
//$sql = "SELECT id FROM app WHERE albumpath = '".$playlistarray[$x][name]."'";
////echo "sql: ".$sql."<br>";
//$result = $conn->query($sql);
//if ($result->num_rows > 0) {
//    while($row = $result->fetch_assoc()) {
//
//        //echo $row['id']."<br>";
//        
//        $playlistarray[$x]["trackid"] = $row['id'];
//        
//
//       }
//     } 
//
//}
////echo "<br><br>";
//if ($verbose){
//echo "Album Search Results With albumid added<br>";
//echo "No of Elements: ".$elements;
//echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
//echo "<br><br><br>";
//}
