<?php
require 'initial.php';

//start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'logout') {
    session_destroy();
    //header("Location: login.php");
    exit;
  }
  
  
    if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
      $error = "Email and password are required.";
    } else {
      $stmt = $conn->prepare("SELECT id, username, password, email, is_admin FROM profiles WHERE email = ? LIMIT 1");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();
      
      $valid = false;
      if ($user) {
        $valid = password_verify($password, $user['password']) || hash_equals((string)$user['password'], $password);
      }
      
      if ($valid) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = (int)$user['is_admin'];
        $_SESSION['display_name'] = $user['username'];

        header("Location: " . ($_SESSION['is_admin'] ? "adminpage.php" : "userpage.php"));
        exit;
      } else {
        $error = "Invalid login.";
      }
    }
  }

  
  //new user registration
  if ($action === 'register') {
    $email        = trim($_POST['email'] ?? '');
    $password     = (string)($_POST['password'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');

    if ($email === '' || $password === '' || $display_name === '') {
      $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Please enter a valid email.";
    } else {
      // check if email exists
      $stmt = $conn->prepare("SELECT id FROM profiles WHERE email = ? LIMIT 1");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $res = $stmt->get_result();
      $existing = $res->fetch_assoc();
      $stmt->close();

      if ($existing) {
        $error = "That email is already registered.";
      } elseif (preg_match('/\s/', $username)) {
        $error = "Username cannot contain spaces or whitespace.";
      } else {
        // map display name into table fields
        $username = substr($display_name, 0, 50);

        $parts = preg_split('/\s+/', $display_name);
        $first_name = substr($parts[0] ?? '', 0, 50);
        $last_name  = substr((count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ''), 0, 50);


        $default_image_path = "/images/users/default/userdefaultprofile.png";

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $is_admin = 0;

        $stmt = $conn->prepare("INSERT INTO profiles (username, password, email, first_name, last_name, image_path, date_created, is_admin) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("ssssssi", $username, $password_hash, $email, $first_name, $last_name,$default_image_path, $is_admin);



        if ($stmt->execute()) {
          $stmt->close();

          $_SESSION['username'] = $username;
          $_SESSION['email'] = $email;
          $_SESSION['is_admin'] = 0;
          $_SESSION['display_name'] = $display_name;
          $_SESSION['image_path'] = 0;
         

          //header("Location: userpage.php");
          exit;
        } else {
          $error = "Account creation failed: " . $stmt->error;
          $stmt->close();
        }
        
        
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Midwest Art Connect | Login</title>
  <link rel="stylesheet" href="style.css">
   
</head>
<body>

  <header>
    <div class="logo">Midwest Art Connect</div>
    <nav>
      <a href="index.html">Home</a>
      <a href="profile.php">Profile</a>
      <a href="portfolios.php">Portfolios</a>
      <a href="projects.php">Projects</a>
      <!-- <a href="businesses.php">Businesses</a> -->
      <a href="login.php">Login</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <section class="hero">
    <h1>Login or Create an Account</h1>
    <p>Use your email and password to access your account. New here? Create an artist account with a display name.</p>
  </section>

  <section class="wrap">

    <div class="card">
      <h2>Login</h2>
      <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="login">

        <label>Email</label>
        <input type="email" name="email" placeholder="name@example.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="your password" required>

        <button class="btn-primary" type="submit">Login</button>
      </form>

      <?php if (!empty($_SESSION['email'])): ?>
        <div class="logged">
          <b>Logged in as:</b> <?php echo h($_SESSION['display_name'] ?? 'User'); ?>
          <br><small><?php echo h($_SESSION['email']); ?></small>

          <form method="post" style="margin-top:10px;">
            <input type="hidden" name="action" value="logout">
            <button class="btn-secondary" type="submit">Logout</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Create New Account</h2>
      <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="register">

        <label>Email</label>
        <input type="email" name="email" placeholder="name@example.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="create a password" required>

        <label>Display Name</label>
        <input type="text" name="display_name" placeholder="Jane Doe" required>

        <button class="btn-primary" type="submit">Create New Account</button>
      </form>

    </div>

  </section>

</body>
</html>