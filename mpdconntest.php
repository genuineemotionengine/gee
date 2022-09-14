<?php

require('mpd.class.php');
echo "ok";
$mpd = new MPD('localhost', 6600);
//if ($mpd === true) {
  //echo "connection successful";
//} else {
  //echo $mpd->get_error();
//}