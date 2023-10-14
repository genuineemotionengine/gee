<?php

//include('config.php');

require_once('mpd.class.php');

$mpd = new mpd("localhost","6600", null);

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

