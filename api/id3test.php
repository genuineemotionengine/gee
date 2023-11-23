<?php

require_once('/var/www/html/api/getid3.php'); 

$flacfile = "/mnt/usb/213261/213256.flac";

$ThisFileInfo = $getID3->analyze($flacfile);

echo '<pre>'.htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE).'</pre>';
