<?php

//include('config.php');

$host = 'localhost';
$port = 6600;
$mpdpassword = null;


require_once('mpd.class.php');

$mpd = new mpd($host,$port,$mpdpassword);

if ( !$mpd->connected)
{
  echo "\nCould not connect to the MPD server\n";
  exit(1);
} else {
  echo "\nConnected to the MPD server\n";  
}


//$mpd = new mpd;

//$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

