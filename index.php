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

$connect = connect();

if ($connect == true) {
  echo "connect successful<br>";
  //pause(1);
} else {
  echo "connect unsuccessful<br>";
  echo $connect->get_error();
}


//connect();
//pause(1);

echo "screen: ".$screen;