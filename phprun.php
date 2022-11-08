<?php

include "dbconn.php";

//$sql = "CREATE TABLE allmusic (
//id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//album varchar(512)
//)";

//$sql="UPDATE musicdata SET location = 'downloads' WHERE location = 'ds';";
//echo $sql."<br>";

//$sql="ALTER TABLE users AUTO_INCREMENT=100001";



$myfile = fopen("/mnt/usb/000Playlists/allmusic.m3u", "r") or die("Unable to open file!");
// Output one line until end-of-file
while(!feof($myfile)) {
    
  $myalbum = fgets($myfile);
  
  $myalbum = chop($myalbum);
  
  $sql="INSERT INTO allmusic (album) VALUES ($myalbum)";
  
  echo $sql."<br>";
  
}
fclose($myfile);



//$conn->query($sql);
//echo mysqli_error($conn)."<br /><br />";
//echo "done";
