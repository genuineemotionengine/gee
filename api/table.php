<?php

require_once('/var/www/html/api/dbconn.php');

//$sql = "DROP TABLE app";
//$result = $conn->query($sql);
//echo mysqli_error($conn)."\n";

$sql = "CREATE TABLE randomcheck (
id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
number varchar(512)
)";

$result = $conn->query($sql);
echo mysqli_error($conn)."\n";


echo "done\n";