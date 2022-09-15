<?php
$pause = 1;
require('mpd.class.php');
echo "read mpd.class.php ok<br>";

$mpd = new mpd('localhost', 6600);

$mySimpleArray = $mpd->current_song();
print_r($mySimpleArray);
$flacfile = $mySimpleArray[0]['name'];

echo "result: ".$flacfile."<br>";


// Load class.
require ('mp3data.php');
echo "read mp3data.php ok<br>";

// Instantiate a new object.
$mp3  = new Mp3Tag();
echo "object ok<br>";

// Get ID3 info.
$data = $mp3->Get( '/mnt/usb/'.$flacfile );
echo "get ok<br>";

// Show results
print_r( $data );
echo "print ok<br>";

foreach ( $data['tag']['picture']['other'] as $image ) {

	echo '<img src="data:' . $image['mime'] . ';charset=utf-8;base64,' . $image['data'] . '" />';
	
}
