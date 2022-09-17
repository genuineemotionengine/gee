
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
   
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
<title>GEE-Lite</title>

<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'>
<meta name="viewport" content="width=device-width, initial-scale=">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function(){
    setInterval(function(){
//        $.getJSON("http://192.168.68.118/api.php", function(result){
//            $('#image').attr('src',result.image);
//            $('#title').text(result.title);
//            $('#artist').text(result.artist);
//            $('#album').text(result.album);       
//        }); 
        $.getJSON("http://192.168.68.118/api.php", function(result-lg){
            $('#image-lg').attr('src',result-lg.image);
            $('#title-lg').text(result-lg.title);
            $('#artist-lg').text(result-lg.artist);
            $('#album-lg').text(result-lg.album);       
        }); 
        
    }); 
}, 100000);
</script>
</head>
<body class="p-3 mb-2 bg-black text-white">
    
    
    <div class="container text-center">
         <div class="d-block d-sm-none">   
             
            <img id='image' src='' /><br>    
            <h1 id='title' class='display-4'></h1>
            <h1 id='artist' class='display-6'></h1>
            <h1 id='album'class='display-6'></h1>
       

       <h1 class='display-4'>mobile</h1>
    </div>
 
    </div>

    <div class="container text-center">
      <div class="d-none d-xl-block">  
        
            <img id='image-lg' src='' /><br>  
            <h1 id='title-lg' class='display-4'></h1>
            <h1 id='artist-lg' class='display-6'></h1>
            <h1 id='album-lg'class='display-6'></h1>

   
       <h1 class='display-4'>mac & audio</h1>
    </div>
       
   
    
</body>
</html>