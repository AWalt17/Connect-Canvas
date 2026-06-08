<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$_SESSION = [];
session_destroy();
//return to homelanding page 
header("Location: index.html");
exit();
?>