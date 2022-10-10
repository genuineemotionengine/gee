<?php
require('mpd.class.php');

$mpd = new mpd('localhost', 6600);

$ThisFileInfo = $mpd->current_song();
    
    //print_r($mySimpleArray);
    echo '<pre>'.print_r($ThisFileInfo, true).'</pre>';
    //echo '<pre>'.htmlentities(print_r($mySimpleArray), ENT_SUBSTITUTE).'</pre>';
    
    
    