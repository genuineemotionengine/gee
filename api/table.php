<?php

$sql = "DROP TABLE app";
$result = $conn->query($sql);
echo mysqli_error($conn)."\n";

$sql = "CREATE TABLE app (
id INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
albumpath varchar(512),
title varchar(512),
artist varchar(512),
album varchar(512),
albumartist varchar(512)
)";

$result = $conn->query($sql);
echo mysqli_error($conn)."\n";


echo "done\n";