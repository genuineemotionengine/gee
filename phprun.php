<?php

parse_str($_SERVER['QUERY_STRING']);

include "dbconn.php";

//$sql = "CREATE TABLE allmusic (
//id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
//album varchar(512)
//)";

//$sql="UPDATE musicdata SET location = 'downloads' WHERE location = 'ds';";
//echo $sql."<br>";

//$sql="ALTER TABLE users AUTO_INCREMENT=100001";


//  $sql="INSERT INTO allmusic (album) VALUES ('$myalbum')";
//  
//  echo $sql."<br>\n";
//  
//
//  
//  $conn->query($sql);
//  
//  echo mysqli_error($conn)."<br><br>";


if ($mode == 1){

$myfile = fopen("/mnt/usb/000Playlists/app.m3u", "r") or die("Unable to open file!");

while(!feof($myfile)) {
    
$myalbum = fgets($myfile);
    
//$myalbum = chop($myalbum);
//$myalbum =  str_replace("'","&#39;",$myalbum);
//echo $myalbum."<br>\n";
  
echo $myalbum."<br>\n";
}
fclose($myfile);

}

if ($mode == 2){
$count = 0;    
$myfile = fopen("/mnt/usb/000Playlists/app.m3u", "w") or die("Unable to open file!");
$sql = "SELECT * FROM allmusic";
$result = $conn->query($sql);
//echo mysqli_error($conn)."<br><br>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $myalbum = $row['album'];
        
        $myalbum = str_replace("&#39;","'",$myalbum);
        
        $myalbum = $myalbum."\n";
        
        //echo $myalbum."<br>\n";
                
        //echo $count."<br>\n";
        
        $myalbumarray[$count] = $myalbum;
        
        $count++;
        

       }
     } 

fclose($myfile);

$elements = count($myalbumarray);

for ($x = 1; $x <= 100; $x++) {
    
    
$random = mt_rand(0, $elements);



//array_unique($a);


$finalarray[$x] = $myalbumarray[$random];
      
//echo $finalarray[$x]."<br>";
      
  }
}

$finalarray = array_unique($finalarray);


$myfile = fopen("/mnt/usb/000Playlists/app.m3u", "w") or die("Unable to open file!");

for ($x = 1; $x <= 100; $x++) {
    
    if ($finalarray[$x] != ""){

  echo $finalarray[$x]."<br>";
  fwrite($myfile, $finalarray[$x]);
    }
}  
fclose($myfile);

//$count++;
//if ($count <= 10){       
//fwrite($myfile, $myalbum);
//}








//$conn->query($sql);
//echo mysqli_error($conn)."<br /><br />";
//echo "done";
