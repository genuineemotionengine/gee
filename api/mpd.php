<?php

//include('config.php');

//$host = null;
//$port = 0;
//$mpdpassword = null;


require_once('mpd.class.php');

$mpd = new mpd('127.0.0.1',6600);

if ( !$mpd->connected)
{
  echo "Could not connect to the MPD server<br><br>";
  exit(1);
} else {
  echo "Connected to the MPD server<br><br>";  
}


//$mpd = new mpd;

//$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

