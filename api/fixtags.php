<?php

require_once('/var/www/html/api/dbconn.php');

//$TextEncoding = 'UTF-8';

require_once('/var/www/html/api/id3/getid3.php');
// Initialize getID3 engine
$getID3 = new getID3;
//$getID3->setOption(array('encoding'=>$TextEncoding));

require_once('/var/www/html/api/id3/write.id3v2.php');
// Initialize getID3 tag-writing module
$tagwriter = new getid3_write_id3v2;

$chk = 0;

$dir = "/mnt/test/";

$dirarray = scandir($dir);

//echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

//for ($x = 2; $x < $elements; $x++) {

$x = 2;
    
$subdir = "/mnt/test/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

//echo htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE);

$subelements = count($subdirarray);

//for ($y = 2; $y < $subelements; $y++) {

$y = 5;
    
    //rename("/mnt/test/".$dirarray[$x]."/".$subdirarray[$y],"/mnt/test/".$dirarray[$x]."/".$count.".flac");
    
    $flacfile = "/mnt/test/".$dirarray[$x]."/".$subdirarray[$y];
    
    echo $flacfile."\n";
    
    //$ThisFileInfo = $getID3->analyze($flacfile);
    
    //echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
    
    $tagstrip = explode('*',$subdirarray[$y]);
    
    echo htmlentities(print_r($tagstrip, true), ENT_SUBSTITUTE);
    
    $title = $tagstrip[1];
    
    echo $title."\n";

    //$tagwriter->filename = '/path/to/file.mp3';
    $tagwriter->filename = $flacfile;

   //$tagwriter->tagformats = array('id3v1', 'id3v2.3');
   //$tagwriter->tagformats = array('id3v2.3');
   $tagwriter->tagformats = array('id3v2');

    // set various options (optional)
    //$tagwriter->overwrite_tags    = true;  // if true will erase existing tag data and write only passed data; if false will merge passed data with existing tag data (experimental)
    //$tagwriter->remove_other_tags = false; // if true removes other tag formats (e.g. ID3v1, ID3v2, APE, Lyrics3, etc) that may be present in the file and only write the specified tag format(s). If false leaves any unspecified tag formats as-is.
    //$tagwriter->tag_encoding      = $TextEncoding;
    //$tagwriter->remove_other_tags = true;

        // populate data array
        $TagData = array(
                'title'           => array('All Nights Long')
        //	'artist'                 => array('The Artist'),
        //	'album'                  => array('Greatest Hits'),
        //	'year'                   => array('2004'),
        //	'genre'                  => array('Rock'),
        //	'comment'                => array('excellent!'),
        //	'track_number'           => array('04/16'),
        //	'popularimeter'          => array('email'=>'user@example.net', 'rating'=>128, 'data'=>0),
        //	'unique_file_identifier' => array('ownerid'=>'user@example.net', 'data'=>md5(time())),
        );
        $tagwriter->tag_data = $TagData;

        //write tags
        if ($tagwriter->WriteID3v2()) {
                echo "Successfully wrote tags\n";
                if (!empty($tagwriter->warnings)) {
                        echo "There were some warnings:".$tagwriter->warnings."\n";
                }
        } else {
                echo "Failed to write tags!".implode($tagwriter->errors)."\n";
        }

//        $chk++;
        $ThisFileInfo = $getID3->analyze($flacfile);

        $title = $ThisFileInfo["tags"]["id3v2"]["title"][0];
        
        echo $title."\n";
        
        echo htmlentities(print_r($ThisFileInfo, true), ENT_SUBSTITUTE);
 //}        
        
//    }
//}
