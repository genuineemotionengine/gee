<?php

parse_str($_SERVER['QUERY_STRING']);
require('mpd.class.php');
require_once('getid3.php');

$mpd = new mpd('localhost', 6600);


  

$mySimpleArray = $mpd->server_status();

echo '<pre>'.htmlentities(print_r($mySimpleArray), ENT_SUBSTITUTE).'</pre>';

$duration = $mySimpleArray['duration'];

$duration = $duration*1000;

echo $duration;