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

echo "var duration;\n";
echo "var current;\n";
//echo "var state;\n";
echo "var play = 'play';\n";
echo "var pause = 'pause';\n";
echo "var currentpos;\n";
echo "var currentprogress;\n";

echo "function nexttrack() {\n";
echo "const xhttp = new XMLHttpRequest();\n";
echo "xhttp.open('GET', 'http://". $ipaddr ."/api.php?service=4');\n";
echo "xhttp.send();\n";
echo "getmeta();\n";
echo "}\n";

echo "function prevtrack() {\n";
echo "const xhttp = new XMLHttpRequest();\n";
echo "xhttp.open('GET', 'http://". $ipaddr ."/api.php?service=3');\n";
echo "xhttp.send();\n";
echo "getmeta();\n";
echo "}\n";

echo "function playpause() {\n";
echo "const xhttp = new XMLHttpRequest();\n";
echo "xhttp.open('GET', 'http://". $ipaddr ."/api.php?service=2');\n";
echo "xhttp.send();\n";
echo "getmeta();\n";
echo "}\n";


//echo "function playrefresh() {\n";
//echo "const xhttp = new XMLHttpRequest();\n";
//echo "xhttp.open('GET', 'http://". $ipaddr ."/api.php?service=5&playl=1');\n";
//echo "xhttp.send();\n";
////echo "duration = 0;\n";
////echo "current= 0;\n";
//echo "getmeta();\n";
//echo "}\n";



echo "function pad ( val ) { return val > 9 ? val : '0' + val; }\n";

echo "function getmeta(){\n";
echo "$.getJSON('http://". $ipaddr ."/api.php?service=1', function(result){\n";
echo "$('#image').attr('src',result.image);\n";
echo "$('#imageipp').attr('src',result.image);\n";
echo "$('#imageipl').attr('src',result.image);\n";
echo "$('#imagexlg').attr('src',result.image);\n";
echo "$('#title').text(result.title);\n";
echo "$('#titleipp').text(result.title);\n";
echo "$('#titleipl').text(result.title);\n";
echo "$('#titlexlg').text(result.title);\n";
echo "$('#artist').text(result.artist);\n";
echo "$('#artistipp').text(result.artist);\n";
echo "$('#artistipl').text(result.artist);\n";
echo "$('#artistxlg').text(result.artist);\n";
echo "$('#album').text(result.album);\n";       
echo "$('#albumipp').text(result.album);\n";
echo "$('#albumipl').text(result.album);\n";
echo "$('#albumxlg').text(result.album);\n";
echo "$('#secondsdur').html(pad(result.duration%60));\n";
echo "$('#minutesdur').html(pad(parseInt(result.duration/60,10)));\n";
echo "$('#secondsduripp').html(pad(result.duration%60));\n";
echo "$('#minutesduripp').html(pad(parseInt(result.duration/60,10)));\n";
echo "$('#secondsduripl').html(pad(result.duration%60));\n";
echo "$('#minutesduripl').html(pad(parseInt(result.duration/60,10)));\n";
echo "$('#secondscur').html(pad(current%60));\n";
echo "$('#minutescur').html(pad(parseInt(current/60,10)));\n";
echo "$('#secondscuripp').html(pad(current%60));\n";
echo "$('#minutescuripp').html(pad(parseInt(current/60,10)));\n";
echo "$('#secondscuripl').html(pad(current%60));\n";
echo "$('#minutescuripl').html(pad(parseInt(current/60,10)));\n";
echo "duration = parseInt(result.duration);\n";
echo "current = parseInt(result.elapsed);\n";
echo "state = result.state;\n";
//echo "$('#playpause').text(result.state);\n";

//echo "if (state = play){\n";
//echo "$('#playpause').html('<i class='bi bi-pause' style='font-size: 6rem; color: white;'></i>');\n";
//echo "}\n";
//
//echo "if (state = pause){\n";
//echo "$('#playpause').html('<i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i>');\n";
//echo "}\n";



echo "});\n";
echo "}\n";




echo "getmeta();\n";


echo "setInterval( function(){\n";
echo "if (state === play){\n";
echo "current = current + 1;\n";
echo "}\n";
echo "currentpos = (current/duration)*100;\n";
echo "currentprogress = currentpos.toFixed(0);\n";

echo "$('#dynamic').css('width', currentprogress + '%');\n";
echo "$('#dynamicipad').css('width', currentprogress + '%');\n";
echo "$('#dynamicipadl').css('width', currentprogress + '%');\n";
echo "$('#secondscur').html(pad(current%60));\n";
echo "$('#minutescur').html(pad(parseInt(current/60,10)));\n";
echo "$('#secondscuripp').html(pad(current%60));\n";
echo "$('#minutescuripp').html(pad(parseInt(current/60,10)));\n";
echo "$('#secondscuripl').html(pad(current%60));\n";
echo "$('#minutescuripl').html(pad(parseInt(current/60,10)));\n";


echo "if (current >= duration){\n";
echo "getmeta();\n";
echo "}\n";

echo "}, 1000);\n";




echo "</script>\n";
echo "</head>\n";
echo "<body class='p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0 me-0'>\n\n";


//*******iPhone portrait**********

echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-block d-sm-none'>\n";
echo "<img id='image' class='img-fluid' src='".$image."' />\n";
//echo "<div id='current'></div>\n";
echo "<br/>\n";
//echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
echo "<a onclick='prevtrack()'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";

echo "<a onclick='playpause()'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";

//if ($play == 1){
//    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
//    
//}
//if ($play == 2){
//    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
//    
//}
//if ($playpause === play){
//    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=1'><i class='bi bi-pause' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
//    
//}
//if ($playpause === pause){
//    echo "<a href='http://". $ipaddr ."/api.php?service=2&pause=0'><i class='bi bi-caret-right' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
//}
//echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";
echo "<a onclick='nexttrack()'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";

echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
//if ($playpause === pause){
//    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
//}else{
    echo "<div class='col-2 text-center'><span id='minutescur'>00</span>:<span id='secondscur'>00</span></div>\n";
    //echo "<div class='col-2 text-center'><span id='time'>00</span></div>\n";
//}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamic' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
//echo "<div id='duration' class='col-2 text-center'>".$duration."</div>\n";
echo "<div class='col-2 text-center'><span id='minutesdur'>00</span>:<span id='secondsdur'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<br/>\n";
echo "<h1 id='title' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artist' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='album' class='display-6'>".$album."</h1>\n";
echo "<a href='http://". $ipaddr ."/api.php?service=5&playl=1'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
//echo "<a onclick='playrefresh()'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>\n";
echo "</div>\n";
echo "</div>\n\n";
//**********************

//*******iPad portait**********


echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-none d-md-block d-lg-none'>\n";
echo "<img id='imageipp' class='img-fluid' src='".$image."' />\n";
echo "<br/>\n";
//echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
echo "<a onclick='prevtrack()'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";

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
//echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";
echo "<a onclick='nexttrack()'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";

echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
if ($playpause === pause){
    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
}else{
    echo "<div class='col-2 text-center'><span id='minutescuripp'>00</span>:<span id='secondscuripp'>00</span></div>\n";
}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamicipad' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
//echo "<div class='col-2 text-center'>".$duration."</div>\n";
echo "<div class='col-2 text-center'><span id='minutesduripp'>00</span>:<span id='secondsduripp'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<br/>\n";
echo "<h1 id='titleipp' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artistipp' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='albumipp' class='display-6'>".$album."</h1>\n";
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
echo "<img id='imageipl' class='img-fluid' src='".$image."' />\n";
echo "</div>\n";
echo "<div class='col'>\n";

//echo "<a href='http://". $ipaddr ."/api.php?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";
echo "<a onclick='prevtrack()'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>&nbsp;&nbsp;\n";

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
//echo "<a href='http://". $ipaddr ."/api.php?service=4'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";
echo "<a onclick='nexttrack()'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></a><br>\n";

echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
if ($playpause === pause){
    echo "<div class='col-2 text-center'>".$elapsedpause."</div>\n";
}else{
    echo "<div class='col-2 text-center'><span id='minutescuripl'>00</span>:<span id='secondscuripl'>00</span></div>\n";
}
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-black' style='height: 5px;'>\n";
echo "<div id='dynamicipadl' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
//echo "<div class='col-2 text-center'>".$duration."</div>\n";
echo "<div class='col-2 text-center'><span id='minutesduripl'>00</span>:<span id='secondsduripl'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";

echo "<h1 id='titleipl' class='display-6'>".$title."</h1>\n";
echo "<h1 id='artistipl' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='albumipl' class='display-6'>".$album."</h1>\n";

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
echo "<img id='imagexlg' src='".$image."' />\n";
echo "<br>\n";
echo "<h1 id='titlexlg' class='display-4'>".$title."</h1>\n";
echo "<h1 id='artistxlg' class='display-6'>".$artist."</h1>\n";
echo "<h1 id='albumxlg' class='display-6'>".$album."</h1>\n";
echo "</div>\n";
echo "</div>\n\n";

//*************************

echo "</body>\n";
echo "</html>\n";
?>
       

