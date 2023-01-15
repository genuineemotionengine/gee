<?php

$mySimpleArray = $mpd->current_song();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

//$sql = "SELECT * FROM app WHERE id = '".$trackid."'";
////echo "sql: ".$sql."<br>";
//$result = $conn->query($sql);
//if ($result->num_rows > 0) {
//    while($row = $result->fetch_assoc()) {
//
//        //echo $row['album']."<br>";
//        
//        $name = $row['albumpath'];
//        
//
//       }
//     } 






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
