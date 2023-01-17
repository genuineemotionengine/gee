<?php
require_once("dbcontroller.php");

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE title like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {



            echo "<li><h4>".$country['title']." - ".$country['artist']." - ".$country['album']."</h4><button type=\x22button\x22 class=\x22bg-black\x22 onclick=\x22insertnext('".$country['id']."')\x22><i class=\x22bi bi-chevron-double-right\x22 style=\x22font-size: 3rem; color: white;\x22></i></button></li>\n";
        }
        
        echo "</ul>\n";

    }

} 
