<?php

parse_str($_SERVER['QUERY_STRING']);

//require_once('dbconn23.php');
//
//$sql = "SELECT * FROM searchterm";
//$results = $conn->query($sql);
//if ($results->num_rows > 0) {
//    while($row = $results->fetch_assoc()) {
//        $term = $row['term'];
//        
//    }
//}


$ipadd = $_SERVER['SERVER_ADDR'];

if ($ipadd == "192.168.68.108"){
    require_once("dbcontroller23.php");
} else {
    require_once("dbcontroller.php");
}


$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    
    if ($term == 1){
        $query ="SELECT * FROM app WHERE title like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    }
    
    if ($term == 2){
        $query ="SELECT * FROM app WHERE album like '" . $_POST["keyword"] . "%' GROUP BY album LIMIT 0,100";
    }
    
    if ($term == 3){
        $query ="SELECT * FROM app WHERE albumartist like '" . $_POST["keyword"] . "%' GROUP BY albumartist LIMIT 0,100";
    }
    
    $result = $db_handle->runQuery($query);
    
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {
            
            
            $albumartist = $country['albumartist'];
            
            if (stripos("$albumartist, Various Artists - ", "Various Artists - ") === 0){
                $albumartist = "Various Artists";
            }

            
            if ($term == 1){             
                echo "<li><h4>".$country['title']." - ".$country['artist']." - ".$country['album']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
            }
            
            if ($term == 2){             
                echo "<li><h4>".$country['album']." - ".$country['albumartist']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
            }
            
            if ($term == 3){             
                echo "<li><h4>".$country['artist']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
            }
            
        
        }
        
        echo "</ul>\n";

    }

} 
