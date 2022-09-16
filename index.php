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

  
  
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
   
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<title>GEE-Lite</title>\n";
//echo "<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'>\n";
//echo "<link rel='stylesheet' href='gee-lite.css'>\n";
//echo "<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>\n";
//echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>\n";
//echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/locale/af.js'></script>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js' integrity='sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8' crossorigin='anonymous'></script>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>\n";
?>

<?php

echo "</head>\n";

echo "<body class='container container-full'>\n";
echo "<div class='container container-full'>\n";


echo "<div class='row text-center'>\n";

        echo "<br><br><br><br><br><br><br><br><br><br><br><br>";

        echo "<img src=".$Image." /><br>";

        echo "<h1>".$title."</h1>";

        echo "<h2>".$artist."</h2>"; 

        echo "<h2>".$album."</h2>"; 

    echo "</div>\n";

echo "</div></body></html>\n"; 

