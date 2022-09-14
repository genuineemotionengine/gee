<?php

require('mpd.class.php');
$mpd = new MPD('localhost', 6600, 'my_password');
if ($mpd === true) {
  // connection successful
} else {
  echo $mpd->get_error();
}