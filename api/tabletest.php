<?php

require_once('/var/www/html/api/dbconn.php');

//$sql = "DROP TABLE playlist";
//$result = $conn->query($sql);
//echo mysqli_error($conn)."\n";
//
$sql = "DROP TABLE apptest";
$result = $conn->query($sql);
echo mysqli_error($conn)."\n";

//$sql = "CREATE TABLE randomcheck (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//number varchar(512)
//)";

$sql = "CREATE TABLE apptest (
id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
albumpath varchar(512),
title varchar(512),
artist varchar(512),
album varchar(512),
albumartist varchar(512),
idalbum varchar(512),
track varchar(512),
genre varchar(512)
)";

//$sql = "CREATE TABLE playlist (
//id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
//albumpath varchar(512),
//title varchar(512),
//artist varchar(512)
//)";

$result = $conn->query($sql);
echo mysqli_error($conn)."\n";


echo "Table dropped\n\n";