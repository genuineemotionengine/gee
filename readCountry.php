<?php
require_once("dbcontroller.php");

$db_handle = new DBController();

if(!empty($_POST["keyword"])) {
    
    $query ="SELECT * FROM app WHERE track like '" . $_POST["keyword"] . "%' ORDER BY track LIMIT 0,100";
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {


            //echo "<li onClick='selectCountry(".$country['artist'].")')><".$country['artist']."</li>\n";
            echo "<li onClick='selectCountry('".$country['track']."');'>".$country['track']."</li>\n";
        }
        
        echo "</ul>\n";

    }

} 
