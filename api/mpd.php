<?php

require_once __DIR__ . "/MphpD/MphpD.php";

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;

$mphpd = new MphpD([
  "host" => "localhost",
  "port" => 6600,
  "timeout" => 5
]);

try{
  $mphpd->connect();
}catch (MPDException $e){
  echo $e->getMessage();
  return false;
}

$mpdarray = $mphpd->player()->current_song();

echo "Current Song:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

$pos = $mpdarray['pos'];

$pos++;

echo "next pos: ".$pos."<br><br>";

$queuearray = $mphpd->queue()->get([$pos,1]);

echo "Queue Get:<br><br>";

echo '<pre>'.htmlentities(print_r($queuearray, true), ENT_SUBSTITUTE).'</pre>';