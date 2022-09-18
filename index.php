
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
        $.getJSON("http://192.168.68.118/api.php", function(result){
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
             
            <img id='image' class='img-fluid' src='' /><br>    
            <h1 id='title' class='display-6'></h1>
            <h1 id='artist' class='display-6'></h1>
            <h1 id='album'class='display-6'></h1>
       
<i class="bi bi-rewind-fill" style="font-size: 6rem; color: white;"></i>&nbsp;&nbsp;
<i class="bi bi-caret-right" style="font-size: 6rem; color: white;"></i>&nbsp;&nbsp;
<i class="bi bi-fast-forward-fill" style="font-size: 6rem; color: white;"></i>
       
    </div>
 
    </div>

    <div class="container text-center">
      <div class="d-none d-xl-block">  
        
            <img id='imagelg' src='' /><br>  
            <h1 id='titlelg' class='display-4'></h1>
            <h1 id='artistlg' class='display-6'></h1>
            <h1 id='albumlg'class='display-6'></h1>

   
       
    </div>
       
   
    
</body>
</html>