<?php
require_once("dbcontroller.php");

require_once('/api/getid3.php');

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE title like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {

            $flacfile = "/mnt/usb/".$country['albumpath'];

            $getID3 = new getID3;

            $ThisFileInfo = $getID3->analyze($flacfile);

            if(isset($ThisFileInfo['comments']['picture'][0])){
                $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($ThisFileInfo['comments']['picture'][0]['data']);
            }


            echo "<li><img src='".$image."' width=20%><h4>".$country['title']." - ".$country['artist']." - ".$country['album']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
        }
        
        echo "</ul>\n";

    }

} 
