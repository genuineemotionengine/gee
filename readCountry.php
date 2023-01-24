<?php
$ipaddr = $_SERVER['SERVER_ADDR'];

require_once('dbconn.php');

$sql = "SELECT term FROM serchterm";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       
        $term = $row['term'];

       }
     } 


if ($ipaddr == "192.168.68.108"){
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
        $query ="SELECT * FROM app WHERE album like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    }
    
    if ($term == 3){
        $query ="SELECT * FROM app WHERE artist like '" . $_POST["keyword"] . "%' ORDER BY title LIMIT 0,100";
    }
    
    
    
    $result = $db_handle->runQuery($query);
    
    if(!empty($result)) {

        echo "<ul id='country-list'>\n";

        foreach($result as $country) {
            
            echo "<li><h4>".$country['title']." - ".$country['artist']." - ".$country['album']."</h4><button type='button' class='bg-black' onclick='insertnext(".$country['id'].")'><i class='bi bi-chevron-double-right' style='font-size: 3rem; color: white;'></i></button></li>\n";
        
        }
        
        echo "</ul>\n";

    }

} 
