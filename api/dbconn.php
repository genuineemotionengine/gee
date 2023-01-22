<?php
$servername = "localhost:3306";
$username = "geeuser";
if ($ipaddr == "192.168.68.108"){
    $password = "Pergamon2023!"; 
} else {
    $password = "Pergamon2022!";
}
$dbname = "geeapp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
    
