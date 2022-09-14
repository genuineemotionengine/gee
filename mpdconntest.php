<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost', 6600);
if ($mpd === true) {
  echo "connection successful";
} else {
  echo $mpd->get_error();
}