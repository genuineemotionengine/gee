<?php

require_once('/var/www/html/api/dbconn.php');

//require_once('/var/www/html/api/mpd.class.php');

require_once('/var/www/html/api/id3/getid3.php');   

//$mpd = new mpd('localhost', 6600);





//****************** Build Database ***************




    
$dir = "/mnt/usb/";

// Sort in ascending order - this is default
$dirarray = scandir($dir);

$elements = count($dirarray);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$a = 1;

for ($x = 2; $x < $elements; $x++) {

//echo $dirarray[$x]."\n";



$subdir = "/mnt/usb/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$subelements = count($subdirarray);

$t = 1;

for ($y = 2; $y < $subelements; $y++) {

//echo $dirarray[$x]."/".$subdirarray[$y]."\n";

$name = $dirarray[$x]."/".$subdirarray[$y];

//echo $name."\n";

$flacfile = "/mnt/usb/".$name;

$getID3 = new getID3;

//sleep(1);


$ThisFileInfo = $getID3->analyze($flacfile);

$track = $ThisFileInfo["tags"]["id3v2"]["track_number"][0];

$title = $ThisFileInfo["tags"]["id3v2"]["title"][0];

$artist = $ThisFileInfo["tags"]["id3v2"]["artist"][0];

$album = $ThisFileInfo["tags"]["id3v2"]["album"][0];

$albumartist = $ThisFileInfo["tags"]["id3v2"]["band"][0];

$genre = $ThisFileInfo["tags"]["id3v2"]["genre"][0];

$title =  str_replace("'","&#39;",$title);
$artist =  str_replace("'","&#39;",$artist);
$album =  str_replace("'","&#39;",$album);
$albumartist =  str_replace("'","&#39;",$albumartist);
$idalbum = $dirarray[$x].$album;


$sql="INSERT INTO app (albumpath, artist, album, title, albumartist, idalbum, track, genre) VALUES ('$name', '$artist', '$album', '$title', '$albumartist', '$idalbum', '$track', '$genre')";

if (!$title or !$artist or !$album or !$albumartist){

echo $sql."\n";
}
echo $t.".";
$conn->query($sql);

if (mysqli_error($conn)){

echo mysqli_error($conn)."\n";
exit;
}


//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['tags'], true), ENT_SUBSTITUTE).'</pre>';
$t++;
}

$albumartist =  str_replace("&#39;","'",$albumartist);
$album =  str_replace("&#39;","'",$album);

echo "\n".$a." - ".$albumartist." - ".$album."\n\n";
$a++;
}


//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';
   
    
//$subdir = "/mnt/usb/".$dirarray[4]."/";
//    
//$subdirarray = scandir($subdir);  
//
//echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    


