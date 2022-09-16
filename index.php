<?php
$pause = 1;
require('mpd.class.php');
//echo "read mpd.class.php ok<br>";

$mpd = new mpd('localhost', 6600);

$mySimpleArray = $mpd->current_song();
echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>';

$flacfile = $mySimpleArray[0]['name'];

$flacfile = "/mnt/usb/".$flacfile;

echo "result: ".$flacfile."<br>";


require_once('getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

// Analyze file and store returned data in $ThisFileInfo
$ThisFileInfo = $getID3->analyze($flacfile);

/*
 Optional: copies data from all subarrays of [tags] into [comments] so
 metadata is all available in one location for all tag formats
 metainformation is always available under [tags] even if this is not called
*/
//$getID3->CopyTagsToComments($ThisFileInfo);

  if(isset($ThisFileInfo['comments']['picture'][0])){
     $Image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
  }
//echo $Image;
echo "<img src=".$Image." />";

//echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments'], true), ENT_SUBSTITUTE).'</pre>';

 //Load class.
//require ('mp3data.php');
//echo "read mp3data.php ok<br>";

// Instantiate a new object.
//$mp3  = new Mp3Tag();
//echo "object ok<br>";
//
//// Get ID3 info.
//$data = $mp3->Get( $flacfile );
//echo "get ok<br>";
//
//// Show results
////print_r( $data );
//echo '<pre>'; print_r($data); echo '</pre>';
//echo "print ok<br>";
//
//foreach ( $data['tag']['picture'] as $image ) {
//
//	echo '<img src="data:' . $image['mime'] . ';charset=utf-8;base64,' . $image['data'] . '" />';
//	
//}
//echo "<br>";
//$imgData = $image['data'];
//echo $imgData."<br>";
////$imgData = str_replace(' ','+',$_POST['image']);
////$imgData =  substr($imgData,strpos($imgData,",")+1);
//$imgData = base64_decode($imgData);
//// Path where the image is going to be saved
//$filePath = $_SERVER['DOCUMENT_ROOT']. '/allmusic/temp2.jpg';
//// Write $imgData into the image file
//$file = fopen($filePath, 'w');
//fwrite($file, $imgData);
//fclose($file);
//
//echo "image: <br>";
//echo "<img src='/allmusic/temp2.jpg' />";
	
//include("flacTags.php");
//
//$ftag=new flacTags($flacfile);
//
//if($ftag->readTags()==false) {
//  echo "ERROR:";
//  echo $ftag->getError();
//}
//else {
//  echo "Vendor String: ";
//  echo $ftag->getVendorString();
//
//  echo "<br><br>Title: ";
//  echo $ftag->getComment("TITLE");
//
//  echo "<br><br>All information:<br><br>";
//  $infos=$ftag->getAllComments();
//  print_r($infos);
//}