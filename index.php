<?php
$pause = 1;
require('mpd.class.php');
echo "read mpd.class.php ok<br>";

$mpd = new mpd('localhost', 6600);

$mySimpleArray = $mpd->current_song();

echo "result: ".$mySimpleArray[0]['basename'];

$flacfile = $mySimpleArray[0]['basename'];

// Load class.
require 'mp3data.php';

// Instantiate a new object.
$mp3  = new Mp3Tag();

// Get ID3 info.
$data = $mp3->Get( '/mnt/usb/'.$flacfile );

// Show results
print_r( $data );

foreach ( $data['tag']['picture'] as $image ) {

	echo '<img src="data:' . $image['mime'] . ';charset=utf-8;base64,' . $image['data'] . '" />';
	
}
