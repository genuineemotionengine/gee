<?php
//$sql = "DROP TABLE allmusic";
//$result = $conn->query($sql);
//echo mysqli_error($conn)."<br>";
//
//$sql = "CREATE TABLE allmusic (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//albumpath varchar(512),
//title varchar(512),
//artist varchar(512),
//album varchar(512),
//albumartist varchar(512)
//)";
//
//$result = $conn->query($sql);
//echo mysqli_error($conn)."<br>";


    
$dir = "/mnt/usb/";


$dirarray = scandir($dir);

$elements = count($dirarray);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

for ($x = 3; $x < $elements; $x++) {

echo $dirarray[$x]."<br>";



$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {

//echo $dirarray[$x]."/".$subdirarray[$y]."<br>";

$name = $dirarray[$x]."/".$subdirarray[$y];

echo $name."<br>";

$flacfile = "/mnt/usb/".$name;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

$title = $ThisFileInfo['tags']['id3v2']['title'][0];

echo "Title: ".$title."<br>";

$artist = $ThisFileInfo['tags']['id3v2']['artist'][0];

echo "Artist: ".$artist."<br>";

$album = $ThisFileInfo['tags']['id3v2']['album'][0];

echo "Album: ".$album."<br>";

$albumartist = $ThisFileInfo['tags']['id3v2']['band'][0];

echo "Album Artist: ".$albumartist."<br>";

$name =  str_replace("'","&#39;",$name);
$title =  str_replace("'","&#39;",$title);
$artist =  str_replace("'","&#39;",$artist);
$album =  str_replace("'","&#39;",$album);
$albumartist =  str_replace("'","&#39;",$albumartist);


$sql="INSERT INTO allmusic (albumpath, title, artist, album, albumartist) VALUES ('$name', '$title', '$artist', '$album' '$albumartist')";

echo $sql."<br><br>\n"; 

//$conn->query($sql);
//
//echo mysqli_error($conn)."<br>";

//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['tags'], true), ENT_SUBSTITUTE).'</pre>';

}
echo $x."<br>";
}


//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';
   
    
//$subdir = "/mnt/usb/".$dirarray[4]."/";
//    
//$subdirarray = scandir($subdir);  
//
//echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    


