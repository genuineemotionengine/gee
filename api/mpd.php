<?php

//include('config.php');

//$host = null;
//$port = 0;
//$mpdpassword = null;


require_once('mpd.class.php');

$mpd = new mpd('localhost', 6600);




//if ( !$mpd->connected)
//{
//  echo "Could not connect to the MPD server<br><br>";
//  exit(1);
//} else {
//  echo "Connected to the MPD server<br><br>";  
//}

if ($mpd === true) { echo "Connected to the MPD server<br><br>"; } else { echo $mpdarray = $mpd->get_error (); }

//$mpd = new mpd;

//$mpdarray = $mpd->server_status();

echo "MPD Response:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';



//$output = array();
//$command = 'mpc';
//exec($command, $output);
//print_r($output);
