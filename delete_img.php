<?php
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['work_id'])) {
    $work_id = (int) $_POST['work_id'];


    $stmt = $conn->prepare("DELETE FROM portfolio WHERE work_id = ?");
    $stmt->bind_param("i", $work_id);
    $stmt->execute();

    header("Location: userpage.php");
    exit;
}