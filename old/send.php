<?php
require('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$statusarray = $mpd->server_status();
    
    print_r($statusarray);
    