
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
   
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
<title>GEE-Lite</title>

<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
$(document).ready(function(){
    setInterval(function(){
        $.getJSON("http://192.168.68.118/api.php", function(result){
            $('#image').attr('src',result.image);
            $('#title').text(result.title);
            $('#artist').text(result.artist);
            $('#album').text(result.album);       
        }); 
    }); 
}, 1000);
</script>
</head>
<body>



    <div>
        
        <img id='image' src='' /><br>  
    
    <h1 id='title' class='display-4'></h1>

    <span id='artist'><h1 class='display-6'></h1></span>

    <span id='album'><h1 class='display-6'></h1></span>
        
    </div>

</body>
</html>