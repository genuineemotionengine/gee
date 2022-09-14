<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('192.168.68.118', 6600);
echo "ok<br>";
if ($mpd === true) {
  echo "connection successful";
} else {
  echo "connection unsuccessful";
  echo $mpd->get_error();
}