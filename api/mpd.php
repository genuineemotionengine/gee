<?php

require_once __DIR__ . "/MphpD/MphpD.php";

use FloFaber\MphpD\MphpD;
use FloFaber\MphpD\MPDException;
use FloFaber\MphpD\Filter;

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

//$mphpd->queue()->shuffle();

$filter = "202139/202138.flac";

$sort = "file";

$mpdarray = $mphpd->queue()->search($filter, $sort);

echo "Searched Song:<br><br>";

echo '<pre>'.htmlentities(print_r($mpdarray, true), ENT_SUBSTITUTE).'</pre>';

//$pos = $mpdarray['pos'];

//$pos++;

//echo "next pos: ".$pos."<br><br>";

//$amount = 5;

//$queuearray = $mphpd->queue()->get();

//echo "Queue Get:<br><br>";

//echo '<pre>'.htmlentities(print_r($queuearray, true), ENT_SUBSTITUTE).'</pre>';