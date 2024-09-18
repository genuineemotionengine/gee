<?php

require_once('dbconn.php');


$sql = "SELECT * FROM app order by albumartist ASC";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
       
       
            $y = 0;
        
            $albumartist = $row['albumartist'];
            $album = $row['album'];
            
            $album =  str_replace("&#39;","'",$album);
            $albumartist =  str_replace("&#39;","'",$albumartist);
            
            $albumarray[$y] = $albumartist." - ".$album;
            $y++;
            
            
        
    }
} 


//$albumarray = array_unique($albumarray);

$elements = count($albumarray);

for ($x = 0; $x < $elements; $x++) {

echo $albumarray[$x]."\n";

}