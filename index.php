<?php
parse_str($_SERVER['QUERY_STRING']);
$ipaddr = $_SERVER['SERVER_ADDR'];
$hosty = gethostname();

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
echo "<head>\n";
echo "<meta name='apple-mobile-web-app-capable' content='yes'/>\n";
echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>\n";
echo "<meta name = 'viewport' content = 'width=device-width, initial-scale = 1, user-scalable = no'/>\n";
echo "<link rel='icon' href='/favicon.ico'/>\n";
echo "<meta name='theme-color' content='#000000'/>\n";
echo "<link rel='apple-touch-icon' href='/logo192.png'/>\n";
echo "<title>".$hosty."</title>\n";
if ($hosty == 'Veronica'){
echo "<link href='css/bootstrap.min.css' rel='stylesheet'/>\n";
echo "<link rel='stylesheet' href='css/bootstrap-icons.css'/>\n";
echo "<script src='js/bootstrap.bundle.min.js'></script>\n";
echo "<script src='js/jquery-3.6.1.min.js'></script>\n";
}else{
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT' crossorigin='anonymous'/>\n";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css'/>\n";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3' crossorigin='anonymous'></script>\n";
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n";
}
echo "<link rel='stylesheet' href='gee-blue.css'/>\n";
echo "<script>\n";

//******* Varibale Decaration *******
echo "var duration;\n";
echo "var current;\n";
echo "var play = 'play';\n";
echo "var pause = 'pause';\n";
echo "var currentpos;\n";
echo "var currentprogress;\n";
echo "var state;\n";
echo "var sterm = 1;\n";

//***********************************

//******* Whole Album ***************
echo "function wholealbum(){\n";
echo "getmeta(1);\n";
echo "$.getJSON('http://". $ipaddr ."/api/?service=8', function(myObj){\n";
echo "let html = '<div>'\n";
echo "for (let x in myObj) {\n";
echo "html += '<div class=\x22border-bottom align-top\x22><br/>";
echo "<h4>' + myObj[x].track + ' - ' + myObj[x].title + ' - ' + myObj[x].artist + '</h4>";
echo "<button type=\x22button\x22 id=\x22nxttrack'+myObj[x].id+'\x22 class=\x22termgrey\x22 onclick=\x22insertnext('+myObj[x].id+')\x22><i class=\x22bi bi-chevron-double-right\x22 style=\x22font-size: 3rem;\x22></i></button>";
echo "</div>';\n";
echo "}\n";
echo "html += '</div>'\n";    
echo "document.getElementById('fullalbum').innerHTML = html;\n";
echo "})\n";
echo "}\n";
//***********************************

//******* Insert Next Track *********
echo "function insertnext(track){\n";
echo "fetch('http://". $ipaddr ."/api/?service=12&id='+ track);\n";
echo "$('#nxttrack'+track).removeClass('termgrey').addClass('termwhite');\n";
echo "$('#nxttracksearch'+track).removeClass('termgrey').addClass('termwhite');\n";
echo "}\n";
//***********************************

//******* Volume Down *********
echo "function volumedown(){\n";
echo "fetch('http://". $ipaddr ."/api/?service=15&mod=-5');\n";
echo "}\n";
//***********************************

//******* Volume Up *********
echo "function volumeup(){\n";
echo "fetch('http://". $ipaddr ."/api/?service=15&mod=+5');\n";
echo "}\n";
//***********************************

//******* Play/Pause *********
echo "function playpause(){\n";
echo "fetch('http://". $ipaddr ."/api/?service=2');\n";
echo "if (state === 1){\n";
echo "state = 2;\n";
//echo "console.log(state);\n";
include ('pauseids.php');
echo "}else{\n";
//echo "if (state === 2){\n";
echo "state = 1;\n";
//echo "console.log(state);\n";
include ('playids.php');
echo "}\n";


echo "}\n";
//***********************************

//******* Search Term *********
echo "function searchterm(term){\n";
//echo "fetch('http://". $ipaddr ."/api/?service='+ term);\n";
echo "sterm = term;\n";


echo "if (sterm === 1){\n";
echo "$('#termtrack').removeClass('termgrey').addClass('termwhite');\n";
echo "$('#termalbum').removeClass('termwhite').addClass('termgrey');\n";
echo "$('#termartist').removeClass('termwhite').addClass('termgrey');\n";
echo "}\n";

echo "if (sterm === 2){\n";
echo "$('#termtrack').removeClass('termwhite').addClass('termgrey');\n";
echo "$('#termalbum').removeClass('termgrey').addClass('termwhite');\n";
echo "$('#termartist').removeClass('termwhite').addClass('termgrey');\n";
echo "}\n";

echo "if (sterm === 3){\n";
echo "$('#termtrack').removeClass('termwhite').addClass('termgrey');\n";
echo "$('#termalbum').removeClass('termwhite').addClass('termgrey');\n";
echo "$('#termartist').removeClass('termgrey').addClass('termwhite');\n";
echo "}\n";

echo "}\n";
//***********************************

//******* Pad ***********************
echo "function pad ( val ) { return val > 9 ? val : '0' + val; }\n";
//***********************************

//******* Get Meta ******************
echo "function getmeta(control){\n";

echo "if (control === 4 || control === 3){\n";
include ('zeroprogids.php');
echo "}\n";

echo "$.getJSON('http://". $ipaddr ."/api/?service=' + control, function(result){\n";
echo "duration = parseInt(result.duration);\n";
echo "current = parseInt(result.elapsed);\n";
//echo "state = result.state;\n";
echo "if (result.state === play){\n";
echo "state = 1;\n";
include ('playids.php');
echo "}\n";
echo "if (result.state === pause){\n";
echo "state = 2;\n";
include ('pauseids.php');
echo "}\n";


include ('metaids.php');



echo "});\n";
echo "}\n";
//***********************************

echo "getmeta(1);\n";

//**** Progress Bar Calulations *****
echo "setInterval( function(){\n";

echo "if (state === 1){\n";
echo "current = current + 1;\n";
echo "}\n";

echo "currentpos = (current/duration)*100;\n";
echo "currentprogress = currentpos.toFixed(0);\n";
include ('progressids.php');

echo "if (current >= duration){\n";
echo "getmeta(1);\n";
echo "}\n";

echo "}, 1000);\n";
//***********************************


echo "</script>\n";

?>

<script>
$(document).ready(function(){
	$("#search-box").keyup(function(){
		$.ajax({
		type: "POST",
		url: "readCountry.php?term=" + sterm,
		data:'keyword='+$(this).val(),
		beforeSend: function(){
			$("#search-box").css("background","#FFF url(LoaderIcon.gif) no-repeat 165px");
		},
		success: function(data){
			$("#suggesstion-box").show();
			$("#suggesstion-box").html(data);
			$("#search-box").css("background","#FFF");
		}
		});
	});
});

function selectCountry(val) {
$("#search-box").val(val);
$("#suggesstion-box").hide();
}


</script>
<?php


echo "</head>\n";
echo "<body style='background: black;' class='p-3 mb-2 bg-black text-white pt-0 ps-0 pe-0 me-0'>\n\n";


//******* iPhone portrait  id 1 **********

echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-block d-sm-none'>\n";
echo "<img id='image1' class='img-fluid' src='black.jpg' />\n";
echo "<button type='button' class='bg-black' onclick='getmeta(3)'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='playpause()'><i id='playpause1' class='bi bi-pause' style='font-size: 5rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(4)'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></button>\n";

echo "<div class='container pt-0 mt-0'>\n";
    echo "<div class='row row-cols-3'>\n";
        echo "<div class='col-2 text-center'><span id='minutescur1'>00</span>:<span id='secondscur1'>00</span></div>\n";
        echo "<div class='col-8'>\n";
            echo "<div class='mt-2'>\n";
                echo "<div class='progress bg-dark' style='height: 5px;'>\n";
                    echo "<div id='dynamic1' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
                echo "</div>\n";
            echo "</div>\n";
        echo "</div>\n";
        echo "<div class='col-2 text-center'><span id='minutesdur1'>00</span>:<span id='secondsdur1'>00</span></div>\n";
    echo "</div>\n";
echo "</div>\n";
echo "<br/>\n";

echo "<div class='container pt-0 mt-0'>\n";
    echo "<div class='row row-cols-3'>\n";
        echo "<div class='col-2 text-center'><button type='button' class='bg-black' onclick='volumedown()'><i class='bi bi-volume-down' style='font-size: 2.3rem; color: white;'></i></button></div>\n";
        echo "<div class='col-8'>\n";
            echo "<div class='pt-3.5'>\n";
                echo "<div class='progress bg-dark' style='height: 5px;'>\n";
                    echo "<div id='voldynamic1' class='progress-bar bg-white' style='width:35%; height: 5px;'></div>\n";
                echo "</div>\n";
            echo "</div>\n";
        echo "</div>\n";
        echo "<div class='col-2 text-center'><button type='button' class='bg-black' onclick='volumeup()'><i class='bi bi-volume-up' style='font-size: 2.3rem; color: white;'></i></button></div>\n";
    echo "</div>\n";
echo "</div>\n";


echo "<br/>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(1)'><i class='bi bi-arrow-clockwise' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(5)'><i class='bi bi-arrow-repeat' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='btn btn-black' onclick='wholealbum()' data-bs-toggle='modal' data-bs-target='#staticBackdrop1'><i class='bi bi-three-dots' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='btn btn-black' data-bs-toggle='modal' data-bs-target='#staticBackdrop2'><i class='bi bi-search' style='font-size: 2.3rem; color: white;'></i></button>\n";
//echo "<button type='button' class='bg-black' onclick='volumedown()'><i class='bi bi-volume-down' style='font-size: 2.3rem; color: white;'></i></button>\n";
//echo "<button type='button' class='bg-black' onclick='volumeup()'><i class='bi bi-volume-up' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<br/>\n";
echo "<h1 id='title1' class='display-6'></h1>\n";
echo "<h1 id='artist1' class='display-6'></h1>\n";
echo "<h1 id='album1' class='display-6'></h1>\n";
//echo "Next: <span id='nexttitle' class='fs-6'></span> - <span id='nextartist' class='fs-6'></span><br/><br/><br/>\n";
echo "</div>\n";
echo "</div>\n\n";

//**********************


//******* iPad portait - id 4 **********


echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-none d-md-block d-lg-none'>\n";
echo "<img id='image4' class='img-fluid' src='black.jpg' />\n";
echo "<br/>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(3)'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(2)'><i id='playpause4' class='bi bi-pause' style='font-size: 5rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(4)'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div class='col-2 text-center'><span id='minutescur4'>00</span>:<span id='secondscur4'>00</span></div>\n";
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-dark' style='height: 5px;'>\n";
echo "<div id='dynamic4' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='col-2 text-center'><span id='minutesdur4'>00</span>:<span id='secondsdur4'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<br/>\n";
echo "<h1 id='title4' class='display-6'></h1>\n";
echo "<h1 id='artist4' class='display-6'></h1>\n";
echo "<h1 id='album4' class='display-6'></h1>\n";
echo "<button type='button' onclick='wholealbum()' class='btn btn-black' data-bs-toggle='modal' data-bs-target='#staticBackdrop'><i class='bi bi-three-dots' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<br/>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(5)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>All Music</h5>\n";
echo "</div>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(6)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>Classical</h5>\n";
echo "</div>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(7)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>Relaxation</h5>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n\n";

//**********************

//******* iPad landscape - id 5 **********


echo "<div class='container-fluid text-center ps-0 pe-0'>\n";
echo "<div class='d-none d-lg-block d-xl-none'>\n";
echo "<div class='row row-cols-2'>\n";
echo "<div class='col'>\n";
echo "<img id='image5' class='img-fluid' src='black.jpg' />\n";
echo "</div>\n";
echo "<div class='col'>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(3)'><i class='bi bi-arrow-left-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(2)'><i id='playpause5' class='bi bi-pause' style='font-size: 5rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(4)'><i class='bi bi-arrow-right-short' style='font-size: 6rem; color: white;'></i></button>\n";
echo "<div class='container pt-0 mt-0'>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div class='col-2 text-center'><span id='minutescur5'>00</span>:<span id='secondscur5'>00</span></div>\n";
echo "<div class='col-8'>\n";
echo "<div class='mt-2'>\n";
echo "<div class='progress bg-dark' style='height: 5px;'>\n";
echo "<div id='dynamic5' class='progress-bar bg-white' style='width: 0%; height: 5px;'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='col-2 text-center'><span id='minutesdur5'>00</span>:<span id='secondsdur5'>00</span></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<h1 id='title5' class='display-6'></h1>\n";
echo "<h1 id='artist5' class='display-6'></h1>\n";
echo "<h1 id='album5' class='display-6'></h1>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(1)'><i class='bi bi-arrow-clockwise' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='btn btn-black' onclick='wholealbum()' data-bs-toggle='modal' data-bs-target='#staticBackdrop1'><i class='bi bi-three-dots' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='btn btn-black' onclick='search()' data-bs-toggle='modal' data-bs-target='#staticBackdrop2'><i class='bi bi-search' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='volumedown()'><i class='bi bi-volume-down' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<button type='button' class='bg-black' onclick='volumeup()'><i class='bi bi-volume-up' style='font-size: 2.3rem; color: white;'></i></button>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(5)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>All Music</h5>\n";
echo "</div>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(6)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>Classical</h5>\n";
echo "</div>\n";
echo "<div>\n";
echo "<button type='button' class='bg-black' onclick='getmeta(7)'><i class='bi bi-arrow-repeat' style='font-size: 3rem; color: white;'></i></button>\n";
echo "<h5>Relaxation</h5>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n\n";

//**********************


//*********** dekstop  id 3 ************

echo "<div class='container text-center'>\n";
echo "<div class='d-none d-xl-block'>\n";  
//echo "<br>\n";
echo "<img id='image3' src='black.jpg'/>\n";
echo "<br>\n";
echo "<h1 id='title3' class='display-4'></h1>\n";
echo "<h1 id='artist3' class='display-6'></h1>\n";
echo "<h1 id='album3' class='display-6'></h1>\n";
echo "</div>\n";
echo "</div>\n\n";

//*************************

//*********** Modal 1 id 2 ***************

echo "<div class='modal fade' id='staticBackdrop1' data-bs-backdrop='static' data-bs-keyboard='false' tabindex='-1' aria-labelledby='staticBackdropLabel' aria-hidden='true'>\n";
echo "<div class='modal-dialog modal-dialog-scrollable'>\n";
echo "<div class='modal-content bg-black'style='background: black;'>\n";
echo "<div class='modal-header'>\n";
echo "<div class='row row-cols-3'>\n";
echo "<div class='col-3'><img id='image2' class='img-fluid' src='' /></div>\n";
echo "<div class='col-7'>\n";
echo "<h3 id='album2'></h3>\n";
echo "<h4 id='albumartist2'></h4>\n";
echo "</div>\n";
echo "<div class='col-1'><button type='button' class='btn btn-sm' data-bs-dismiss='modal' aria-label='Close'><i class='bi bi-x' style='font-size: 3rem; color: white;'></i></button></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "<div class='modal-body'>\n";
echo "<div id='fullalbum'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";

//*************************

//*********** Modal 2 id 6 ***************

echo "<div class='modal fade' id='staticBackdrop2' data-bs-backdrop='static' data-bs-keyboard='false' tabindex='-1' aria-labelledby='staticBackdropLabel' aria-hidden='true'>\n";
echo "<div class='modal-dialog modal-dialog-scrollable'>\n";
echo "<div class='modal-content bg-black'style='background: black;'>\n";
echo "<div class='modal-header'>\n";
echo "<button type='button' id='termtrack' class='termwhite' onclick='searchterm(1)'><i class='bi bi-music-note-beamed' style='font-size: 2.3rem;'></i></button>\n";
echo "<button type='button' id='termalbum' class='termgrey' onclick='searchterm(2)'><i class='bi bi-vinyl' style='font-size: 2.3rem;'></i></button>\n";
echo "<button type='button' id='termartist' class='termgrey' onclick='searchterm(3)'><i class='bi bi-mic' style='font-size: 2.3rem;'></i></button><br><br>\n";
echo "<input class='form-control input-sm bg-black text-white' type='text' id='search-box' name='".$token."'/><br>\n";
echo "<button type='button' class='btn btn-sm' data-bs-dismiss='modal' aria-label='Close'><i class='bi bi-x' style='font-size: 3rem; color: white;'></i></button>\n";
echo "</div>\n";
echo "<div class='modal-body'>\n";
echo "<div id='suggesstion-box'></div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";
echo "</div>\n";

//*************************


echo "</body>\n";
echo "</html>\n";

       
