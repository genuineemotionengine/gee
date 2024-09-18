<?php

require_once('dbconn.php');


$sql = "SELECT * FROM app order by albumartist ASC";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       
        
        
            echo $row['albumartist']." - ".$row['album']."\n";
        
        
    }
} 


