<?php
require('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$mySimpleArray = $mpd->current_song();
    
    print_r($mySimpleArray);