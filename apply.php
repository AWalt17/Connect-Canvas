<?php
require 'initial.php'; 
require 'business_connect.php';


if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$success = '';
$error = '';

$project_id = (int)($_GET['project_id'] ?? 0);
if ($project_id <= 0) { 
    $error = "Invalid project.";
}

if (empty($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$user = null;
if (!$error) {
    $uname = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT id, username, email FROM profiles WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = "User account not found. Please log in again.";
        
    }
}


$project = null;
if (!$error) {
    $stmt = $b_conn->prepare("SELECT id, title, category, description, budget, deadline, contact, created_at FROM projects WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$project) {
        $error = "Project not found.";
        
    }
}


if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'apply_project') {
    $message = trim($_POST['message'] ?? '');
    
    $uid = (int)$user['id'];
    
    $stmt = $conn->prepare("INSERT INTO applications (user_id, project_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $uid, $project_id, $message);
    
    if ($stmt->execute()) {
        $success = "You have successfully applied to this project.";
    } else {
        $error = "Application submit failed: " . $stmt->error;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Apply to Project</title>
    <link rel="stylesheet" href="style.css">
    <style>
    .wrap { padding: 30px 8%; max-width: 900px; margin: 0 auto; }
    .card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 6px 18px rgba(0,0,0,0.06); padding:24px; margin-bottom:18px; }
    label { display:block; margin-top:12px; font-weight:bold; color:#1f2937; }
    textarea { width:100%; min-height:120px; padding:10px 12px; margin-top:6px; border:1px solid #d1d5db; border-radius:10px; background:#fff; }
    .btn-primary { background:#3EA6DE; color:#fff; border:none; padding:12px 14px; border-radius:10px; cursor:pointer; font-weight:bold; }
    .btn-secondary { background:#fff; color:#1f2937; border:1px solid #d1d5db; padding:12px 14px; border-radius:10px; cursor:pointer; font-weight:bold; text-decoration:none; display:inline-block; }
    .alert { padding:12px 14px; border-radius:10px; margin-bottom:16px; }
    .alert-success { background:#D4EDDA; color:#155724; border:1px solid #C3E6CB; }
    .alert-error { background:#F8D7DA; color:#721c24; border:1px solid #F5C6CB; }
    .meta { color:#6b7280; font-size:14px; margin-top:6px; }
  </style>
</head>
<body>

<header>
  <div class="logo">Connect Canvas</div>
  <nav>
    <a href="index.html">Home</a>
    <a href="profile.php">Profile</a>
    <a href="portfolios.php">Portfolios</a>
    <a href="projects.php">Projects</a>
    <a href="login.php">Login</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="wrap">

  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?php echo e($success); ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?php echo e($error); ?></div>
  <?php endif; ?>

  <?php if (!$error && $project): ?>
    <div class="card">
      <h2 style="margin-top:0;"><?php echo e($project['title']); ?></h2>
      <div class="meta">
        Category: <?php echo e($project['category']); ?>
        <?php if (!empty($project['deadline'])): ?> • Deadline: <?php echo e($project['deadline']); ?><?php endif; ?>
        <?php if (!empty($project['budget'])): ?> • Budget: <?php echo e($project['budget']); ?><?php endif; ?>
      </div>
      <p style="margin-top:12px; color:#4b5563;"><?php echo nl2br(e($project['description'])); ?></p>
    </div>

    <?php if (!$success): ?>
      <div class="card">
        <h3 style="margin-top:0;">Application Form</h3>
        <p class="meta">Applying as: <b><?php echo e($user['username']); ?></b> (<?php echo e($user['email']); ?>)</p>

        <form method="post">
          <input type="hidden" name="action" value="apply_project">

          <label>Message (optional)</label>
          <textarea name="message" placeholder="Add a short note about your experience, availability, or portfolio..."></textarea>

          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-primary" type="submit">Submit Application</button>
            <a class="btn-secondary" href="projects.php">Cancel</a>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card">
        <a class="btn-secondary" href="projects.php">Back to Projects</a>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</div>
</body>
</html>
