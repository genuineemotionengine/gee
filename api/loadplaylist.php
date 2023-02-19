<?php

$mpd->playlist_clear();

$playlist = "app";

$count = 0;    

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $myalbum = $row['albumpath'];
        
//        $myalbum = str_replace("&#39;","'",$myalbum);
        
        $myalbum = $myalbum."\n";
        
        $myalbumarray[$count] = $myalbum;
        
        $count++;

       }
     } 
     

$elements = count($myalbumarray);

shuffle($myalbumarray);

$myfile = fopen("/mpd/playlists/app.m3u", "w") or die("Unable to open file!");

for ($x = 0; $x < $elements; $x++) {

  fwrite($myfile, $myalbumarray[$x]);
    
}  
fclose($myfile);

$mpd->load_playlist($playlist);

$mpd->update_db();

$mpd->repeat(1);

$mpd->play(0);
