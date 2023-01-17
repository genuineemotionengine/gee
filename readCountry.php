<?php
require_once("dbcontroller.php");

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE title like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {


            //echo "<li onClick='selectCountry(".$country['artist'].")')><".$country['artist']."</li>\n";
            echo "<li onClick='selectCountry('".$country['id']."');'>".$country['title']." - ".$country['artist']."</li>\n";
        }
        
        echo "</ul>\n";

    }

} 
