<?php

$servername = "localhost";
$database = "infost490sp2601_users";     // database 
$username = "infost490sp2601";     // cpaneluser 
$password = "canvasconnect2026"; // user password

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>