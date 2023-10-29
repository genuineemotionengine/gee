<?php

parse_str($_SERVER['QUERY_STRING'], $qsarray);

$term = $qsarray['term'];

//$ipadd = $_SERVER['SERVER_ADDR'];

require_once("dbcontroller.php");

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
                echo "<li><h4>".$country['title']."<br>".$country['artist']."<br>".$country['album']."</h4>"
                        . "<button type='button' class='termgrey' data-bs-dismiss='modal' class='termgrey' onclick='playnext(".$country['id'].")'><i class='bi bi-chevron-right' style='font-size: 3rem;'></i></button>"
                        . "<button type='button' id='nxttracks".$country['id']."' class='termgrey' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem;'></i></button>"
                        . "</li>\n";
            }
            
            if ($term == 2){             
                echo "<li><h4>".$country['album']." - ".$albumartist."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
            }
            
            if ($term == 3){             
                echo "<li><h4>".$country['artist']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
            }
            
        
        }
        
        echo "</ul>\n";

    }

} 
