<?php

$servername = "localhost";
$database = "infost490sp2601_business";     // database 
$username = "infost490sp2601";     // cpaneluser 
$password = "canvasconnect2026"; // user password

$b_conn = new mysqli($servername, $username, $password, $database);

if ($b_conn->connect_error) {
    die("Business Connection failed: " . $b_conn->connect_error);
}

?>