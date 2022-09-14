<?php
//$pause = 1;
require('mpd.class.php');
//echo "ok<br>";


header("Content-type: text/plain");
$mpd = new MPD('localhost', 6600);
$mpd->connect();
$status = $mpd->getCurrentSong();
if (empty($status)) {
    $status = array();
}
$status = array_merge($status, $mpd->getStatus());
$status['repeat'] = $status['repeat'] == 1 ? true : false;
$status['random'] = $status['random'] == 1 ? true : false;
$mpd->disconnect();
echo json_encode($status);