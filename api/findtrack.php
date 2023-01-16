<?php

$mySimpleArray = $mpd->current_song();

if ($verbose){
echo "Current Song";
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>'; 
echo "<br><br><br>";    
}

$sql = "SELECT albumpath FROM app WHERE id = '".$id."'";
echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       
        $uri = $row['albumpath'];

       }
     } 

if ($verbose){
echo "uri: ".$uri."<br>";
}
   

$pos = $mySimpleArray[0]['Pos'];

$pos++;

if ($verbose){
echo "uri: ".$uri."<br>";

echo "pos: ".$pos."<br>";
}

