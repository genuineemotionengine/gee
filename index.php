<?php
parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];
$hosty = gethostname();

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
echo "<meta name='apple-mobile-web-app-capable' content='yes'>\n";
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<meta name = 'viewport' content = 'width=device-width, initial-scale = 1'/>\n";
echo "<title>".$hosty."</title>\n";
//if ($hosty == 'Veronica'){
//echo "<link href='css/bootstrap.min.css' rel='stylesheet'/>\n";
//echo "<link rel='stylesheet' href='css/bootstrap-icons.css'/>\n";
//echo "<script src='js/bootstrap.bundle.min.js'></script>\n";
//echo "<script src='js/jquery-3.6.1.min.js'></script>\n";
//
//}else{
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'/>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css'/>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3' crossorigin='anonymous'></script>\n";
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n";
echo "<link rel='stylesheet' href='gee.css'/>\n";
//}
echo "<script>\n";

echo "var duration;\n";
echo "var current;\n";
echo "var play = 'play';\n";
echo "var pause = 'pause';\n";
echo "var currentpos;\n";
echo "var currentprogress;\n";
echo "var state;\n";

echo "function pad ( val ) { return val > 9 ? val : '0' + val; }\n";

echo "function getmeta(control){\n";
echo "$.getJSON('http://". $ipaddr ."/api/?service=' + control, function(result){\n";
echo "duration = parseInt(result.duration);\n";
echo "current = parseInt(result.elapsed);\n";
echo "state = result.state;\n";
echo "$('#image').attr('src',result.image);\n";
echo "$('#title').text(result.title);\n";
echo "$('#artist').text(result.artist);\n";
echo "$('#album').text(result.album);\n";
echo "$('#secondsdur').html(pad(result.duration%60));\n";
echo "$('#minutesdur').html(pad(parseInt(result.duration/60,10)));\n";
echo "$('#secondscur').html(pad(current%60));\n";
echo "$('#minutescur').html(pad(parseInt(current/60,10)));\n";
echo "});\n";
echo "}\n";

echo "getmeta(1);\n";

echo "setInterval( function(){\n";

echo "if (state === play){\n";
echo "current = current + 1;\n";

echo "}\n";
echo "currentpos = (current/duration)*100;\n";
echo "currentprogress = currentpos.toFixed(0);\n";
echo "$('#dynamic').css('width', currentprogress + '%');\n";


echo "$('#secondscur').html(pad(current%60));\n";
echo "$('#minutescur').html(pad(parseInt(current/60,10)));\n";



echo "if (current >= duration){\n";
echo "getmeta(1);\n";
//echo "location.reload();";
//echo "wholealbum();\n";

echo "}\n";

echo "}, 1000);\n";



echo "</script>\n";

echo "</head>\n";
echo "<body style='background: black;' class='p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0 me-0'>\n\n";


//*******iPhone portrait**********

echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-block d-sm-none'>\n";
echo "<img id='image' class='img-fluid' src='black.jpg' />\n";
echo "<a href='http://". $ipaddr ."/api/?service=3'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></a>\n";
echo "<a href='http://". $ipaddr ."/api/?service=2'><i id='playp' class='bi bi-pause' style='font-size: 5rem; color: white;'></i></a>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(4)'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div class='col-2 text-center'><span id='minutescur'>00</span>:<span id='secondscur'>00</span></div>\n";
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-dark' style='height: 5px;'>\n";
echo "<div id='dynamic' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='col-2 text-center'><span id='minutesdur'>00</span>:<span id='secondsdur'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<br/>\n";
echo "<h1 id='title' class='display-6'></h1>\n";
echo "<h1 id='artist' class='display-6'></h1>\n";
echo "<h1 id='album' class='display-6'></h1>\n";
echo "<a href='http://". $ipaddr ."/'><i class='bi bi-arrow-clockwise' style='font-size: 3rem; color: white;'></i></a>\n";
echo "<button type='button' onclick='wholealbum()' class='btn btn-black' data-bs-toggle='modal' data-bs-target='#staticBackdrop'><i class='bi bi-three-dots' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<a href='http://". $ipaddr ."/api/?service=5&playl=1'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a><br/><br/><br/>\n";
//echo "Next: <span id='nexttitle' class='fs-6'></span> - <span id='nextartist' class='fs-6'></span><br/><br/><br/>\n";
//echo "<span class='fs-6'>".$hosty."</span><br/><br/>";


echo "<a href='http://". $ipaddr ."/api/?service=9&vol=70'><i class='bi bi-volume-down' style='font-size: 3rem; color: white;'></i></a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "<a href='http://". $ipaddr ."/api/?service=9&vol=100'><i class='bi bi-volume-up' style='font-size: 3rem; color: white;'></i></a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "</div>\n";
echo "</div>\n\n";
//**********************




echo "</body>\n";
echo "</html>\n";

       

