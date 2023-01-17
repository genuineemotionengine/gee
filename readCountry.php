<?php
require_once("dbcontroller.php");

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE artist like '" . $_POST["keyword"] . "%' ORDER BY artist LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $row) {

            echo "<li onClick='selectCountry(".$row['artist'].")')><".$row['artist']."</li>\n";
            
        }
        
        echo "</ul>\n";

    }

} 
