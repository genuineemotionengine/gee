<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost');
if ($mpd === true) {
  echo "connection successful";
} else {
  echo "connection unsuccessful";
  echo $mpd->get_error();
}