<?php
$pause = '1';
require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost','');
pause($pause);
echo "ok<br>";
//echo $mpd."<br>";
if ($mpd == true) {
  echo "connection successful<br>";
  
} else {
  echo "connection unsuccessful<br>";
  echo $mpd->get_error();
}



//echo "screen: ".$screen;