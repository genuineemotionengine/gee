<?php
$pause = 1;
require('mpd.class.php');
echo "ok<br>";
$mpd = new MPD('localhost','');

echo "ok<br>";
//echo $mpd."<br>";
if ($mpd == true) {
  echo "initialise successful<br>";
  //pause(1);
} else {
  echo "initialise unsuccessful<br>";
  echo $mpd->get_error();
}

$mpd->connect();
if (isset($_GET['enable'])) {
    $id = intval($_GET['enable']);
} else {
    if (isset($_GET['disable'])) {
        $id = intval($_GET['disable']);
    } else {
        echo json_encode($mpd->outputs());
    }
}
$mpd->disconnect();