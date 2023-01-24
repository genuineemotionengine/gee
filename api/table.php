<?php

require_once('/var/www/html/api/dbconn23.php');

//$sql = "DROP TABLE app";
//$result = $conn->query($sql);
//echo mysqli_error($conn)."\n";

//$sql = "CREATE TABLE randomcheck (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//number varchar(512)
//)";

//$sql = "CREATE TABLE app (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//albumpath varchar(512),
//title varchar(512),
//artist varchar(512),
//album varchar(512),
//albumartist varchar(512),
//idalbum varchar(512),
//track varchar(512),
//genre varchar(512)
//)";

$sql = "CREATE TABLE searchterm (
id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
term varchar(512)
)";

$result = $conn->query($sql);
echo mysqli_error($conn)."\n";


echo "done\n";