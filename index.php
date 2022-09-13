<?php
    //set_include_path('./mpd/');
require "./mpd/mpd.class.php";
//include "mpd.class.php";
$mpd = new MPD('localhost', 6600, 'Pergamon2022!');
if ($mpd === true) {
  echo "connection successful";
} else {
  echo $mpd->get_error();
}