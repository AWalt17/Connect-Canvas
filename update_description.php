<?php
require_once 'connect.php'; // your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    $descriptors = trim($_POST['descriptors'] ?? '');

        if ($work_id > 0) {
        $stmt = $conn->prepare("UPDATE portfolio SET descriptors = ? WHERE work_id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $descriptors, $work_id);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    }
}

header("Location: userpage.php");
exit;
?>