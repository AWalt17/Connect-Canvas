<?php
require 'connect.php';
$query = "SELECT * FROM profiles";
$result = mysqli_query($conn, $query);

echo '<pre>';

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        print_r(array_keys($row));
        echo "\n\n";
    }
}

echo '</pre>';
?>