<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost', 6600,'',10);
echo "ok<br>";
if ($mpd === true) {
  echo "connection successful<br>";
} else {
  echo "connection unsuccessful<br>";
  echo $mpd->get_error();
}