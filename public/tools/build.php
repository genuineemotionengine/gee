<?php



require_once '/var/www/app/core/bootstrap.php';

require_once '/var/www/app/api/getid3/getid3.php';

$conn = gee_db();


//****************** Build Database ***************




    
$dir = "/mnt/music/";

// Sort in ascending order - this is default
$dirarray = scandir($dir);

$elements = count($dirarray);

//echo htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE);

$a = 1;

for ($x = 2; $x < $elements; $x++) {

//echo $dirarray[$x]."\n";



$subdir = "/mnt/music/".$dirarray[$x]."/";

$subdirarray = scandir($subdir);

$subelements = count($subdirarray);

$t = 1;

for ($y = 2; $y < $subelements; $y++) {

//echo $dirarray[$x]."/".$subdirarray[$y]."\n";

$name = $dirarray[$x]."/".$subdirarray[$y];

//echo $name."\n";

$flacfile = "/mnt/music/".$name;

$getID3 = new getID3;

//sleep(1);


$ThisFileInfo = $getID3->analyze($flacfile);

if (isset($ThisFileInfo["tags"]["id3v2"]["track_number"])){   
    $track = $ThisFileInfo["tags"]["id3v2"]["track_number"][0];
} else {
    $track = $ThisFileInfo["tags"]["vorbiscomment"]["tracknumber"][0];
}

if (isset($ThisFileInfo["tags"]["id3v2"]["title"])){
    $title = $ThisFileInfo["tags"]["id3v2"]["title"][0];    
} else {
    $title = $ThisFileInfo["tags"]["vorbiscomment"]["title"][0];
}

if (isset($ThisFileInfo["tags"]["id3v2"]["artist"])){
    $artist = $ThisFileInfo["tags"]["id3v2"]["artist"][0];
    } else {
    $artist = $ThisFileInfo["tags"]["vorbiscomment"]["artist"][0];
}

if (isset($ThisFileInfo["tags"]["id3v2"]["album"])){    
    $album = $ThisFileInfo["tags"]["id3v2"]["album"][0];
} else {
    $album = $ThisFileInfo["tags"]["vorbiscomment"]["album"][0];
}

if (isset($ThisFileInfo["tags"]["id3v2"]["band"])){   
    $albumartist = $ThisFileInfo["tags"]["id3v2"]["band"][0];
} else {
    $albumartist = $ThisFileInfo["tags"]["vorbiscomment"]["albumartist"][0];
}

if (isset($ThisFileInfo["tags"]["id3v2"]["genre"])){    
    $genre = $ThisFileInfo["tags"]["id3v2"]["genre"][0];
} else {
    $genre = $ThisFileInfo["tags"]["vorbiscomment"]["genre"][0];
}

$title =  str_replace("'","&#39;",$title);
$artist =  str_replace("'","&#39;",$artist);
$album =  str_replace("'","&#39;",$album);
$albumartist =  str_replace("'","&#39;",$albumartist);
$idalbum = $dirarray[$x].$album;

$stmt = $conn->prepare("
    INSERT INTO app (
        albumpath,
        artist,
        album,
        title,
        albumartist,
        idalbum,
        track,
        genre
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit;
}

$stmt->bind_param(
    "ssssssss",
    $name,
    $artist,
    $album,
    $title,
    $albumartist,
    $idalbum,
    $track,
    $genre
);

if (!$stmt->execute()) {
    echo "Execute failed: " . $stmt->error . "\n";
    $stmt->close();
    exit;
}

$stmt->close();


$t++;
}

$albumartist =  str_replace("&#39;","'",$albumartist);
$album =  str_replace("&#39;","'",$album);

echo "\n".$a." - ".$albumartist." - ".$album." - ".$dirarray[$x];
$a++;
}


///***************** Load Playlist ***********************

$playlist = "app";

$count = 0;

$sql = "SELECT albumpath FROM app WHERE genre != 'Relaxation'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "\n";
    exit;
}

$stmt->bind_param("s", $album);

if (!$stmt->execute()) {
    echo "Execute failed: " . $stmt->error . "\n";
    $stmt->close();
    exit;
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $myalbumarray[$count++] = $row['albumpath'] . "\n";
}

$stmt->close();

//$result = $conn->query($sql);
//if ($result->num_rows > 0) {
//    while($row = $result->fetch_assoc()) {
//
//        $myalbum = $row['albumpath'];       
//        
//        $myalbum = $myalbum."\n";
//        
//        $myalbumarray[$count] = $myalbum;
//        
//        $count++;
//
//    }
//} 
    

$elements = count($myalbumarray);

shuffle($myalbumarray);

$myfile = fopen("/var/lib/mpd/playlists/app.m3u", "w") or die("Unable to open file!");

for ($x = 0; $x < $elements; $x++) {

  fwrite($myfile, $myalbumarray[$x]);
    
}  
fclose($myfile);
    


