<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost','');
echo "ok<br>";
//echo $mpd."<br>";
if ($mpd == true) {
  echo "connection successful<br>";
  
} else {
  echo "connection unsuccessful<br>";
  echo $mpd->get_error();
}

pause();

//echo "screen: ".$screen;