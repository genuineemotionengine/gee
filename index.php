<?php
$pause = 1;
require('mpd.class.php');
echo "read mpd.class.php ok<br>";

$mpd = new mpd('localhost', 6600);
    if ($mpd == true) {
      echo "initialise mpd ok<br>";
    } else {
    echo "initialise mpd not ok<br>";
      echo $mpd->get_error();
     }

$mpd->current_song();
    if ($mpd == true) {
      echo "current song mpd ok<br>";
    } else {
      echo "current song mpd not ok<br>";
      echo $mpd->get_error();
    }

//$currentsong = $mpd->current_song();    


echo '<pre>'; print_r($mpd->current_song()); echo '</pre>';

//echo "<br><br>";
//
//echo $mpd[31];