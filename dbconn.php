<?php
$servername = "localhost:3306";
$username = "geeuser";
$password = "Pergamon2022!";
$dbname = "geeapp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "connection success";
}
    
