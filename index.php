<?php
$pause = 1;
require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost','');

echo "ok<br>";
//echo $mpd."<br>";
if ($mpd == true) {
  echo "connection successful<br>";
  //pause(1);
} else {
  echo "connection unsuccessful<br>";
  echo $mpd->get_error();
}

connect();
pause(1);

echo "screen: ".$screen;