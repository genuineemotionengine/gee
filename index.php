<?php
$pause = 1;
require('mpd.class.php');
//echo "read mpd.class.php ok<br>";

$mpd = new mpd('localhost', 6600);

$mySimpleArray = $mpd->current_song();
//echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>';

$flacfile = $mySimpleArray[0]['name'];

$album = $mySimpleArray[0]['Album'];

$artist = $mySimpleArray[0]['Artist'];

$title = $mySimpleArray[0]['Title'];
$flacfile = "/mnt/usb/".$flacfile;

//echo "result: ".$flacfile."<br>";


require_once('getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($flacfile);



  if(isset($ThisFileInfo['comments']['picture'][0])){
     $Image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
  }


echo $title."<br>";
 
echo $artist."<br>"; 

echo $album."<br>"; 
  
echo "<img src=".$Image." />";

