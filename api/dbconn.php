<?php
$servername = "localhost:3306";
$username = "geeuser";
$password = "tangram";  
$dbname = "gee";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}