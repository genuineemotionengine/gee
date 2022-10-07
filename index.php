<?php
parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];
$hosty = gethostname();
require('mpd.class.php');
require_once('getid3.php');

$mpd = new mpd('localhost', 6600);
    
$status = $mpd->server_status();

$duration = $status['duration'];

$durations = explode(".",$duration);

$progduration = $durations[0];

$duration = gmdate("i:s", $durations[0]);

$elapsed = $status['elapsed'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

$refresh = $durations[0] - $elapsed;

//$refresh = $refresh*1000;

$elapsedpause = $elapseds[0]-1;

$elapsedpause = gmdate("i:s", $elapsedpause);

$playpause = $status['state'];

$mySimpleArray = $mpd->current_song();
    
    //print_r($mySimpleArray);
      
$flacfile = $mySimpleArray[0]['name'];

$album = $mySimpleArray[0]['Album'];

$artist = $mySimpleArray[0]['Artist'];

$title = $mySimpleArray[0]['Title'];

$flacfile = "/mnt/usb/".$flacfile;

$getID3 = new getID3;

$ThisFileInfo = $getID3->analyze($flacfile);
//echo '<pre>'.htmlentities(print_r($ThisFileInfo['comments']['picture'][0], true), ENT_SUBSTITUTE).'</pre>';

if(isset($ThisFileInfo['comments']['picture'][0])){
    $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
}



echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<meta name = 'viewport' content = 'width=device-width, initial-scale = 1'/>\n";
//if ($refresh){
//echo "<meta http-equiv='refresh' content='".$refresh."'/>\n";
//}
echo "<title>".$hosty."</title>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'/>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css'/>\n";
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n";
echo "<script>\n";
echo "$(document).ready(function(){\n";

echo "var duration = ".$progduration.";\n";
echo "var current = ".$elapsed.";\n";
echo "setInterval( function(){\n";
echo "current = current + 1;\n";
echo "var currentpos = (current/duration)*100;\n";
echo "var currentprogress = currentpos.toFixed(0);\n";
echo "$('#dynamic').css('width', currentprogress + '%');\n";
echo "$('#dynamicipad').css('width', currentprogress + '%');\n";
echo "$('#dynamicipadl').css('width', currentprogress + '%');\n";
echo "$('#seconds').html(duration%60);\n";
echo "$('#minutes').html(parseInt(duration/60,10));\n";


echo "if (current === duration){\n";
echo "$.getJSON('http://". $ipaddr ."/api.php?service=1', function(result){\n";
echo "$('#image').attr('src',result.image);\n";
//echo "$('#imagelg').attr('src',result.image);\n";
echo "$('#title').text(result.title);\n";
//echo "$('#titlelg').text(result.title);\n";
echo "$('#artist').text(result.artist);\n";
//echo "$('#artistlg').text(result.artist);\n";
echo "$('#album').text(result.album);\n";       
//echo "$('#albumlg').text(result.album);\n";
echo "$('#elapsed').text(result.elapsed);\n";
echo "$('#seconds').html(result.duration%60);\n";
echo "$('#minutes').html(parseInt(result.duration/60,10));\n";

echo "$('#duration').text(result.duration);\n";
echo "});\n";
echo "current = result.elapsed;\n";
echo "current = current.toFixed(0);\n";
echo "$('#time').text(current);\n";
echo "}\n";

echo "}, 1000);\n"; 


//echo "var sec = ".$elapsed.";\n";
//echo "function pad ( val ) { return val > 9 ? val : '0' + val; }\n";
//echo "setInterval( function(){\n";
//echo "$('#seconds').html(pad(++sec%60));\n";
//echo "$('#minutes').html(pad(parseInt(sec/60,10)));\n";
////echo "$('#secondsipad').html(pad(++sec%60));\n";
////echo "$('#minutesipad').html(pad(parseInt(sec/60,10)));\n";
////echo "$('#secondsipadl').html(pad(++sec%60));\n";
////echo "$('#minutesipadl').html(pad(parseInt(sec/60,10)));\n";
//echo "}, 1000);\n"; 





echo "});\n";
echo "</script>\n";
echo "</head>\n";
echo "<body class='p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0 me-0'>\n\n";

//*******iPhone portrait**********

echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-block d-sm-none'>\n";
echo "<img id='image' class='img-fluid' src='".$image."' />\n";
echo "<br/>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
if ($play == 1){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($play == 2){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === play){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === pause){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
}
echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";
echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
if ($playpause === pause){
    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
}else{
    echo "<div class='col-2 text-center'><span id='minutes'>00</span>:<span id='seconds'>00</span></div>\n";
    //echo "<div class='col-2 text-center'><span id='time'>00</span></div>\n";
}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamic' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div id='duration' class='col-2 text-center'>".$duration."</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<br/>\n";
echo "<h1 id='title' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artist' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='album' class='display-6'>".$album."</h1>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "</div>\n";
echo "</div>\n\n";
//**********************

//*******iPad portait**********


echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-none d-md-block d-lg-none'>\n";
echo "<img id='image' class='img-fluid' src='".$image."' />\n";
echo "<br/>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
if ($play == 1){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($play == 2){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === play){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === pause){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
}
echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";

echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
if ($playpause === pause){
    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
}else{
    echo "<div class='col-2 text-center'><span id='minutesipad'>00</span>:<span id='secondsipad'>00</span></div>\n";
}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamicipad' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='col-2 text-center'>".$duration."</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<br/>\n";
echo "<h1 id='title' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artist' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='album' class='display-6'>".$album."</h1>\n";
echo "<br/>\n";
/////////
echo "<div class='row row-cols-3'>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=1'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>All Music</h5>\n";
echo "</div>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=2'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>Classical</h5>\n";
echo "</div>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=3'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>Relaxation</h5>\n";
echo "</div>\n";

echo "</div>\n";
/////////////
echo "</div>\n";
echo "</div>\n\n";

//**********************

//*******iPad landscape**********


echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-none d-lg-block d-xl-none'>\n";


echo "<div class='row row-cols-2'>\n";
echo "<div class='col'>\n";
echo "<img id='image' class='img-fluid' src='".$image."' />\n";
echo "</div>\n";
echo "<div class='col'>\n";

echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
if ($play == 1){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($play == 2){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === play){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
    
}
if ($playpause === pause){
    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
}
echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";

echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
if ($playpause === pause){
    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
}else{
    echo "<div class='col-2 text-center'><span id='minutesipadl'>00</span>:<span id='secondsipadl'>00</span></div>\n";
}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamicipadl' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='col-2 text-center'>".$duration."</div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<h1 id='title' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artist' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='album' class='display-6'>".$album."</h1>\n";

echo "<div class='row row-cols-3'>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=1'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>All Music</h5>\n";
echo "</div>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=2'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>Classical</h5>\n";
echo "</div>\n";

echo "<div>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=3'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<h5>Relaxation</h5>\n";
echo "</div>\n";

echo "</div>\n";




echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n\n";

//**********************



//***********dekstop************

echo "<div class='container text-center'>\n";
echo "<div class='d-none d-xl-block'>\n";  
echo "<br><br><br><br><br><br><br>\n";
echo "<img id='imagelg' src='".$image."' />\n";
echo "<br>\n";
echo "<h1 id='titlelg' class='display-4'>".$title."</h1>\n";
echo "<h1 id='artistlg' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='albumlg' class='display-6'>".$album."</h1>\n";
echo "</div>\n";
echo "</div>\n\n";

//*************************

echo "</body>\n";
echo "</html>\n";
?>
       

