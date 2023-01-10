<?php

$sql = "SELECT albumpath FROM app WHERE id = '".$trackid."'";
//echo "sql: ".$sql."<br>";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        //echo $row['album']."<br>";
        
        $name = $row['albumpath'];
        

       }
     } 


     

    
$elements = count($playlistarray);

  for ($x = 0; $x <= $elements; $x++) {
  if ($name != $playlistarray[$x][name]){
      unset($playlistarray[$x]);
  }else{
      $t = $x;
  }
}

if ($verbose){
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
}


$uri = $playlistarray[$t]['name'];

$pos = $statusarray['song'];

$pos++;

if ($verbose){
echo "uri: ".$uri."<br>";

echo "pos: ".$pos."<br>";
}

