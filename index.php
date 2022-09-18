<?php
parse_str($_SERVER['QUERY_STRING']);
require('mpd.class.php');

$mpd = new mpd('localhost', 6600);
    
$status = $mpd->server_status();

//echo '<pre>'.print_r($status['state']).'</pre>';    
    

$playpause = $status['state'];
//echo $playpause;
    
?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
   
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
<title>GEE-Lite</title>

<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
<meta name = "viewport" content = "width=device-width, initial-scale = 1, shrink-to-fit = no">
<meta name = "theme-color" content = "#000000">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function(){
    setInterval(function(){
        $.getJSON("http://192.168.68.118/api.php?service=1", function(result){
            $('#image').attr('src',result.image);
            $('#imagelg').attr('src',result.image);
            $('#title').text(result.title);
            $('#titlelg').text(result.title);
            $('#artist').text(result.artist);
            $('#artistlg').text(result.artist);
            $('#album').text(result.album);       
            $('#albumlg').text(result.album);       
        }); 
    }); 
}, 100000);
</script>
</head>
<body class="p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0">
    
    
    <div class="container-fluid text-center ps-0 pe-0">
         <div class="d-block d-sm-none">   
             
            <img id='image' class='img-fluid' src='black.jpg' /><br> 
                
                    
                    


<?php

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


?>



<table class="text-center">
  <tr>
    <td>02.53</td>
    <td>05.11</td>
 
  </tr>

</table>
                     
                    
                    
            <h1 id='title' class='display-6'></h1>
            <h1 id='artist' class='display-6'></h1>
            <h1 id='album'class='display-6'></h1>
            
       
<?php
echo "<a href='http://192.168.68.118/api.php?service=5'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></a>";
?>
       
    </div>
 
    </div>

    <div class="container text-center">
      <div class="d-none d-xl-block">  
        
            <img id='imagelg' src='' /><br>  
            <h1 id='titlelg' class='display-4'></h1>
            <h1 id='artistlg' class='display-6'></h1>
            <h1 id='albumlg'class='display-6'></h1>
            </a>
   
       
    </div>
       
   
    
</body>
</html>