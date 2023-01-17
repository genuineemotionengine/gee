<?php
require_once("dbcontroller.php");

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE album like '" . $_POST["keyword"] . "%' ORDER BY artist LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {


            //echo "<li onClick='selectCountry(".$country['artist'].")')><".$country['artist']."</li>\n";
            echo "<li onClick='selectCountry('".$country['album']."');'>".$country['album']."</li>\n";
        }
        
        echo "</ul>\n";

    }

} 
