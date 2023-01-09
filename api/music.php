<?php

require_once('/var/www/html/api/dbconn.php');

require_once('/var/www/html/api/getid3.php');

$dir = "/mnt/usb/";

$dirarray = scandir($dir);

echo '<pre>'.htmlentities(print_r($dirarray, true), ENT_SUBSTITUTE).'</pre>';

$elements = count($dirarray);

for ($x = 3; $x < 4; $x++) {

$random = mt_rand(1000000000, 9999999999);

echo $random."\n";

$dup = 0;

$sql = "SELECT number FROM randomcheck";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {

        if ($random == $row['number']){
            $dup = 1;
        }
        

       }
     } 

if ($dup == 0){
    
    $sql = "INSERT INTO randomcheck (number) VALUES ($random)";
    
    echo $sql."\n"; 

    $conn->query($sql);

    if (mysqli_error($conn)){

    echo mysqli_error($conn)."\n";
    exit;
    } 
    
}else {
        echo $random. " is a duplicate\n";
    }


rename("/mnt/usb/".$dirarray[$x],"/mnt/usb/".$random);
    
}
    