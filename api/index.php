<?php

parse_str($_SERVER['QUERY_STRING']);

$ipaddr = $_SERVER['SERVER_ADDR'];

require_once('dbconn.php');

require_once('mpd.class.php');

require_once('getid3.php');   

$mpd = new mpd('localhost', 6600);

//***************** Just Get Meta **********************

if ($service == 1){ 
    
    include ('getmeta.php');

}


    
//***************** Next **********************

if ($service == 4){ 
    
    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];
        
    $mpd->next();
    
    if ($state === 'play'){
        $pause = 0;
    }

    if ($state === 'pause'){
        $pause = 1;
    }

    $mpd->pause($pause);    
    
    include ('getmeta.php');

}



//***************** Pause **********************

if ($service == 2){

    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];

    if ($state === 'play'){
        $pause = 1;
    }

    if ($state === 'pause'){
        $pause = 0;
    }

    $mpd->pause($pause);

    include ('getmeta.php');
    
}

//***************** Previous **********************

if ($service == 3){
    
    $statusarray = $mpd->server_status();

    $state = $statusarray['state'];
       
    $mpd->prev();
    
    if ($state === 'play'){
        $pause = 0;
    }

    if ($state === 'pause'){
        $pause = 1;
    }

    $mpd->pause($pause);

    include ('getmeta.php');

}


//***************** Restart Playlist **********************

if ($service == 5){
    
   
$mpd->playlist_clear();
    
   

$playlist = "app";
$count = 0;    

$sql = "SELECT * FROM allmusic";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        $myalbum = $row['album'];
        
        $myalbum = str_replace("&#39;","'",$myalbum);
        
        $myalbum = $myalbum."\n";
        
        $myalbumarray[$count] = $myalbum;
        
        $count++;

       }
     } 
     

$elements = count($myalbumarray);

shuffle($myalbumarray);

$myfile = fopen("/mnt/usb/000Playlists/app.m3u", "w") or die("Unable to open file!");

for ($x = 0; $x < $elements; $x++) {

  fwrite($myfile, $myalbumarray[$x]);
    
}  
fclose($myfile);

$mpd->load_playlist($playlist);

$mpd->repeat(1);

$mpd->play(0);

include ('getmeta.php');
 
}

//***************** load classical playlist **********************

if ($service == 6){  
    
    

$playlist = "classical";

$mpd->load_playlist($playlist);

$mpd->playlist_shuffle();

$mpd->repeat(1);

$mpd->play(0);



    

    
}


//***************** load relaxation playlist **********************

if ($service == 7){  

   
$playlist = "relaxation";

$mpd->load_playlist($playlist);

$mpd->playlist_shuffle();

$mpd->repeat(1);

$mpd->play(0);


}


//***************** get album list **********************

if ($service == 8){

include ('getalbum.php'); 
    
echo json_encode($playlistarray);

}

//****************** Play Next ***************

if ($service == 12){

echo "service 12";    

    
include ('getalbum.php');


//$sql = "SELECT album FROM allmusic WHERE id = '".$trackid."'";
//echo "sql: ".$sql."<br>";
//$result = $conn->query($sql);
//if ($result->num_rows > 0) {
//    while($row = $result->fetch_assoc()) {
//
//        echo $row['album']."<br>";
//        
//        $name = $row['album'];
//        
//
//       }
//     } 
//
//
//     
//
//    
//$elements = count($playlistarray);
//
//  for ($x = 0; $x <= $elements; $x++) {
//  if ($name != $playlistarray[$x][name]){
//      unset($playlistarray[$x]);
//  }else{
//      $t = $x;
//  }
//}
//
//if ($verbose){
//echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
//}
//
//
//$uri = $playlistarray[$t]['name'];
//
//$pos = $statusarray['song'];
//
//$pos++;
//
//if ($verbose){
//echo "uri: ".$uri."<br>";
//
//echo "pos: ".$pos."<br>";
//}
//
//$results = $mpd->playlist_add_id($uri, $pos);
//
//if ($verbose){
//
//echo '<pre>'.htmlentities(print_r($results, true), ENT_SUBSTITUTE).'</pre>';
//
//
//}
//
//include ('getmeta.php');

}

//****************** Play Now ***************

if (service == 13){
    
include ('getalbum.php');    
    
$elements = count($playlistarray);

  for ($x = 0; $x <= $elements; $x++) {
  if ($name != $playlistarray[$x][name]){
      unset($playlistarray[$x]);
  }else{
      $t = $x;
  }
}

if ($verbose){
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
}


$uri = $playlistarray[$t]['name'];

$pos = $statusarray['song'];

$pos++;

if ($verbose){
echo "uri: ".$uri."<br>";

echo "pos: ".$pos."<br>";
}

$results = $mpd->playlist_add_id($uri, $pos);


$mpd->next();  



if ($verbose){

echo '<pre>'.htmlentities(print_r($results, true), ENT_SUBSTITUTE).'</pre>';


}

include ('getmeta.php');

}




//***************** set vol + **********************

if ($service == 9){  
    


$mpd->setvol($vol);

header("Location: http://". $ipaddr ."");



    
}

//***************** set vol - **********************

if ($service == 15){  
    


$mpd->setvol($vol);

header("Location: http://". $ipaddr ."");



    
}

//***************** serach **********************

if ($service == 10){  
    
    


$what = $title;

echo $title."<br>";

$type = "Title";

$playlistarray = $mpd->search($type, $what);

$elements = count($playlistarray);

echo "Album Search Results<br>";
echo "No of Elements: ".$elements;
echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
echo "<br><br><br>";





for ($x = 0; $x <= $elements; $x++) {
  if ($album != $playlistarray[$x][Album]){
      unset($playlistarray[$x]);
  }
}



echo '<pre>'.htmlentities(print_r($playlistarray, true), ENT_SUBSTITUTE).'</pre>';
    
}
    


//****************** Up Next ***************

if ($service == 11){
    
$statusarray = $mpd->server_status();    
  
$playlist = "relaxation";

$pos = $statusarray['song'];

$pos = "+".$pos;

//$range = "0:";

echo "pos: ".$pos."<br>";

$loadarray = $mpd->load_next($playlist, $pos);

echo '<pre>'.htmlentities(print_r($loadarray, true), ENT_SUBSTITUTE).'</pre>';


}





//****************** Build Database ***************

if ($service == 14){
    
$dir = "/mnt/usb/";

// Sort in ascending order - this is default
$dirarray = scandir($dir);



echo '<pre>'.htmlentities(print_r($dirarray[23], true), ENT_SUBSTITUTE).'</pre>';
   
    
$subdir = "/mnt/usb/".$dirarray[4]."/";
    
$subdirarray = scandir($subdir);  

echo '<pre>'.htmlentities(print_r($subdirarray, true), ENT_SUBSTITUTE).'</pre>';
    
}

if ($service == 16){
    
$statusarray = $mpd->server_status();

//echo '<pre>'.htmlentities(print_r($statusarray, true), ENT_SUBSTITUTE).'</pre>';
    
$elapsed = $statusarray['elapsed'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

//echo $elapsed."<br><br><br>";

$rows = [
    
'elapsed' => $elapsed
        
];

echo json_encode($rows);

}