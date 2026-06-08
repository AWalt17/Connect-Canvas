<?php
include 'initial.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT file_type, image
        FROM portfolio
        WHERE owner = $id
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $image = mysqli_fetch_assoc($result);

    echo '<img src="data:' . $image['file_type'] . ';base64,' . base64_encode($image['image']) . '" alt="Image">';
} else {
    echo 'Image not found';
}
?>