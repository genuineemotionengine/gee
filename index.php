<?php
//$pause = 1;
//require('mpd.class.php');
//require_once('getid3.php');
////echo "read mpd.class.php ok<br>";
//
//$mpd = new mpd('localhost', 6600);
//
//$mySimpleArray = $mpd->current_song();
////echo '<pre>'.htmlentities(print_r($mySimpleArray, true), ENT_SUBSTITUTE).'</pre>';
//
//$flacfile = $mySimpleArray[0]['name'];
//
//$album = $mySimpleArray[0]['Album'];
//
//$artist = $mySimpleArray[0]['Artist'];
//
//$title = $mySimpleArray[0]['Title'];
//$flacfile = "/mnt/usb/".$flacfile;
//
////echo "result: ".$flacfile."<br>";
//
//
//
//
//// Initialize getID3 engine
//$getID3 = new getID3;
//
//// Analyze file and store returned data in $ThisFileInfo
//$ThisFileInfo = $getID3->analyze($flacfile);
//
//
//
//  if(isset($ThisFileInfo['comments']['picture'][0])){
//     $Image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
//  }

$x=1;
  
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
   
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<title>GEE-Lite</title>\n";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'>\n";
//echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js' integrity='sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8' crossorigin='anonymous'></script>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>\n";
echo "<script type = 'text/javascript' src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.jsw'></script>\n";
//echo "<script type = 'text/javascript' language = 'javascript'>\n";
?>

<script>
$(document).ready(function(){
  setInterval(function(){
    $.getJSON("http://192.168.68.118/api.php", function(result){
        
        
        
            $('#image').attr('src',result.image); 
        
        }); 
        
    }); 
  
}, 1000);

</script>


<?php
//echo "$(document).ready(function() {\n";
//
//    echo "setInterval(function(){\n";
//    
//        echo "$.getJSON('http://192.168.68.118/api.php', function(jd) {;\n";
//        echo "$('#image').attr('src',jd.image);\n";
//        echo "$('#title').text(jd.title);\n";
//        echo "$('#artist').text(jd.artist);\n";
//        echo "$('#album').text(jd.album);\n";
//    
//    echo "});\n";  
//    
//    
//    echo "}, 10000);\n";
//
//echo "});\n";
//echo "</script>\n";


echo "</head>\n";

echo "<body class='p-3 mb-2 bg-black text-white'>\n";
echo "<div class='container'>\n";


echo "<div class='text-center'>\n";

        echo "<br><br><br><br><br><br><br><br>";

        echo "<img id='image' src='' />";

        echo "<span id='title'><h1 class='display-4'></h1></span>";

        echo "<span id='artist'><h1 class='display-6'></h1></span>"; 

        echo "<span id='album'><h1 class='display-6'></h1></span>"; 

    echo "</div>\n";

echo "</div></body></html>\n"; 

