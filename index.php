<?php
$pause = 1;
require('mpd.class.php');
echo "read mpd.class.php ok<br>";


//header("Content-type: text/plain");
$mpd = new mpd('localhost', 6600);
//    if ($mpd == true) {
//      echo "initialise mpd ok<br>";
//    } else {
//      echo $mpd->get_error();
//    }


//$mpd->Connect();
//    if ($mpd == true) {
//      echo "connected to mpd ok<br>";
//    } else {
//      echo $mpd->get_error();
//    }
//


//$mpd->Pause();
//    if ($mpd == true) {
//      echo "paused mpd ok<br>";
//    } else {
//      echo $mpd->get_error();
//    }


//$status = $mpd->getCurrentSong();
//if (empty($status)) {
//    $status = array();
//}
//$status = array_merge($status, $mpd->getStatus());
//$status['repeat'] = $status['repeat'] == 1 ? true : false;
//$status['random'] = $status['random'] == 1 ? true : false;
//$mpd->disconnect();
//echo json_encode($status);
