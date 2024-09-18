<?php

require_once('dbconn.php');


$sql = "SELECT * FROM app order by albumartist ASC";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
            $albumtest = $row['album'];
            $albumtest =  str_replace("&#39;","'",$albumtest);        
       
            if ($album != $albumtest){
        
            $albumartist = $row['albumartist'];
            $album = $row['album'];
            
            $album =  str_replace("&#39;","'",$album);
            $albumartist =  str_replace("&#39;","'",$albumartist);
            
            
        
            echo $albumartist." - ".$album."\n";
            }
        
    }
} 


