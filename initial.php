<?php
session_start();
include 'connect.php';

//pulling users from database//
$profiles= [ ];
$sql = "SELECT profiles.id AS id,
               profiles.username AS username,
               portfolio.file_type AS file_type,
               portfolio.image AS image,
               portfolio.owner AS owner
        FROM profiles
        JOIN portfolio
          ON profiles.id = portfolio.owner";
$result= mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $profiles[] = $row;
    }
}
?>