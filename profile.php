<?php
// profile.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Not logged in 
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

//admin user goes to admin landing page
if ((int)($_SESSION['is_admin'] ?? 0) === 1) {
  header("Location: adminpage.php"); 
  exit();
}

// Regular user goes user landing page
header("Location: userpage.php");
exit();
?>