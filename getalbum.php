<?php

$statusarray = $mpd->server_status(); 

if ($verbose){
echo "Server Status";
echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
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
