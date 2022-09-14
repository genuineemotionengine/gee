<?php

require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('127.0.1.1', 22,'',10);
echo "ok<br>";
if ($mpd === true) {
  echo "connection successful<br>";
} else {
  echo "connection unsuccessful<br>";
  echo $mpd->get_error();
}