<?php
$pause = 1;
require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost','');

echo "ok<br>";
//echo $mpd."<br>";
if ($mpd == true) {
  echo "initialise successful<br>";
  //pause(1);
} else {
  echo "initialise unsuccessful<br>";
  echo $mpd->get_error();
}

$members = $this->pause(1);

//connect();
//pause(1);

echo "screen: ".$screen;