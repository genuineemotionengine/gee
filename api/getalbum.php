<?php

$mySimpleArray = $mphpd->player()->current_song();

$albumtracks = array();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}


$sql = "SELECT idalbum FROM app WHERE albumpath = '".$mySimpleArray['file']."'";
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
$sql = "SELECT * FROM app WHERE idalbum = '".$idalbum."'";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $id = $row['id'];
        $albumpath = $row['albumpath'];
        $title = $row['title'];
        $artist = $row['artist'];
//        $albumartist = $row['albumartist'];
        $track = $row['track'];
        
        $title =  str_replace("&#39;","'",$title);
        $artist =  str_replace("&#39;","'",$artist);
//        $albumartist =  str_replace("&#39;","'",$albumartist);
        
        if ($verbose) {
        echo "id: ".$id."<br>";
        echo "albumpath: ".$albumpath."<br>";
        echo "title: ".$title."<br>";
        echo "artist: ".$artist."<br>";
//        echo "albumartist: ".$albumartist."<br>";
        echo "track: ".$track."<br><br>";
        }
  
        $albumtracks[$count] = array(
            "id" => $id,
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

