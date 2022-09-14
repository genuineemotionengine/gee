<?php

require('mpd.class.php');
$mpd = new MPD('localhost', 6600, 'Pergamon2022!');
if ($mpd === true) {
  // connection successful
} else {
  echo $mpd->get_error();
}