<?php

include "dbconn.php";

$sql = "CREATE TABLE allmusic (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
album varchar(512)
)";

//$sql="UPDATE musicdata SET location = 'downloads' WHERE location = 'ds';";
//echo $sql."<br>";

//$sql="ALTER TABLE users AUTO_INCREMENT=100001";


$conn->query($sql);
echo mysqli_error($conn)."<br /><br />";
echo "done";
