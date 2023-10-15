<?php

//include('config.php');

$host = 'localhost';
$port = 6600;
$mpdpassword = null;


require_once('mpd.class.php');

$mpd = new mpd($host,$port);

if ( !$mpd->connected)
{
  echo "Could not connect to the MPD server<br>";
  //exit(1);
} else {
  echo "Connected to the MPD server";  
}


//$mpd = new mpd;

//$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

