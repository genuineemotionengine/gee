<?php
parse_str($_SERVER['QUERY_STRING']);
require('mpd.class.php');
require_once('getid3.php');

$mpd = new mpd('localhost', 6600);
    
$status = $mpd->server_status();

$duration = $status['duration'];

$durations = explode(".",$duration);

$duration = gmdate("i:s", $durations[0]);

$elapsed = $status['elapsed'];

$elapseds = explode(".",$elapsed);

$elapsed = $elapseds[0];

$refresh = $durations[0] - $elapsed;

//$refresh = $refresh*1000;

$elapsedpause = $elapseds[0]-1;

$elapsedpause = gmdate("i:s", $elapsedpause);

$playpause = $status['state'];



echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<meta name = 'viewport' content = 'width=device-width, initial-scale = 1'>\n";
//echo "<meta http-equiv='refresh' content='".$refresh."'>\n";
echo "<title>GEE-Lite</title>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css'>\n";
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n";
echo "<script>\n";
echo "$(document).ready(function(){\n";

echo "setInterval(function(){\n";
echo "$.getJSON('http://192.168.68.118/api.php?service=1', function(result){\n";
echo "$('#image').attr('src',result.image);\n";
echo "$('#imagelg').attr('src',result.image);\n";
echo "$('#title').text(result.title);\n";
echo "$('#titlelg').text(result.title);\n";
echo "$('#artist').text(result.artist);\n";
echo "$('#artistlg').text(result.artist);\n";
echo "$('#album').text(result.album);\n";      
echo "$('#albumlg').text(result.album);\n";
echo "$('#albumlg').text(result.album);\n";
echo "});\n"; 
echo "}, text(result.refresh));\n";

echo "var sec = ".$elapsed.";\n";
echo "function pad ( val ) { return val > 9 ? val : '0' + val; }\n";
echo "setInterval( function(){\n";
echo "$('#seconds').html(pad(++sec%60));\n";
echo "$('#minutes').html(pad(parseInt(sec/60,10)));\n";
echo "}, 1000);\n";  

echo "});\n";
echo "</script>\n";
echo "</head>\n";
echo "<body class='p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0 me-0'>\n";
echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-block d-sm-none'>\n";
echo "<img id='image' class='img-fluid' src='black.jpg' /><br>\n";
echo "<a href='http://192.168.68.118/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;";
if ($play == 1){
    echo "<a href='http://192.168.68.118/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;";
    
}
if ($play == 2){
    echo "<a href='http://192.168.68.118/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;";
    
}
if ($playpause === play){
    echo "<a href='http://192.168.68.118/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;";
    
}
if ($playpause === pause){
    echo "<a href='http://192.168.68.118/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;";
}
echo "<a href='http://192.168.68.118/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>";
echo "<div class='container text-center'>\n";
echo "<div class='row row-cols-2'>\n";
if ($playpause === pause){
    echo "<div class='col'>".$elapsedpause."</div>";
}else{
    echo "<div class='col'><span id='minutes'>00</span>:<span id='seconds'>00</span></div>";
}
echo "<div class='col'>".$duration."</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<br>\n";
echo "<h1 id='title' class='display-6'></h1>\n";
echo "<h1 id='artist' class='display-6'></h1>\n";
echo "<h1 id='album' class='display-6'></h1>\n";
echo "<a href='http://192.168.68.118/api.php?service=5'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>";
echo "</div>\n";
echo "</div>\n";

echo "<div class='container text-center'>\n";
echo "<div class='d-none d-xl-block'>\n";  
echo "<br><br><br><br><br><br><br><br><br><br>\n";
echo "<img id='imagelg' src='".$image."' /><br>\n";
echo "<h1 id='titlelg' class='display-4'></h1>\n";
echo "<h1 id='artistlg' class='display-6'></h1>\n";
echo "<h1 id='albumlg' class='display-6'></h1>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";
?>
       

