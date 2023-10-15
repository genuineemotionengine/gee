<?php

//include('config.php');

//$host = null;
//$port = 0;
//$mpdpassword = null;


require('mpd.class.php');

$mpd = new mpd('localhost',6601);

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

