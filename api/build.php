<?php

require_once('/var/www/html/api/dbconn23.php');

//require_once('/var/www/html/api/mpd.class.php');

require_once('/var/www/html/api/getid3.php');   

//$mpd = new mpd('localhost', 6600);





//****************** Build Database ***************




    
$dir = "/mnt/usb/";

// Sort in ascending order - this is default
$dirarray = scandir($dir);

$elements = count($dirarray);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

for ($x = 3; $x < $elements; $x++) {

//echo $dirarray[$x]."\n";



$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$subelements = count($subdirarray);

for ($y = 2; $y < $subelements; $y++) {

//echo $dirarray[$x]."/".$subdirarray[$y]."\n";

$name = $dirarray[$x]."/".$subdirarray[$y];

//echo $name."\n";

$flacfile = "/mnt/usb/".$name;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);

$title = $ThisFileInfo['tags']['id3v2']['title'][0];

//echo "Title: ".$title."\n";

$artist = $ThisFileInfo['tags']['id3v2']['artist'][0];

//echo "Artist: ".$artist."\n";

$album = $ThisFileInfo['tags']['id3v2']['album'][0];

//echo "Album: ".$album."\n";

$albumartist = $ThisFileInfo['tags']['id3v2']['band'][0];

//echo "Album Artist: ".$albumartist."\n";

//$name =  str_replace("'","&#39;",$name);
$title =  str_replace("'","&#39;",$title);
$artist =  str_replace("'","&#39;",$artist);
$album =  str_replace("'","&#39;",$album);
$albumartist =  str_replace("'","&#39;",$albumartist);
$idalbum = $dirarray[$x].$album;


$sql="INSERT INTO app (albumpath, title, artist, album, albumartist, idalbum) VALUES ('$name', '$title', '$artist', '$album', '$albumartist', '$idalbum')";

echo $sql."\n"; 

$conn->query($sql);

if (mysqli_error($conn)){

echo mysqli_error($conn)."\n";
exit;
}


//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['tags'], true), ENT_SUBSTITUTE).'</pre>';

}
echo $x." done\n";
}


//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';
   
    
//$subdir = "/mnt/usb/".$dirarray[4]."/";
//    
//$subdirarray = scandir($subdir);  
//
//echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    


