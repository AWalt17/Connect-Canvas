<?php
include 'initial.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['is_admin'] == 1) {
    header("Location: index.php");
    exit();
}



function profile_img_for_user($username) {
    $username = trim($username);

    // Try exact username + folder-safe variant (spaces -> underscores)
    $usernamewithoutwhitespaces = [
        $username,
        preg_replace('/[^A-Za-z0-9_-]+/', '_', str_replace(' ', '_', $username)),
    ];

    // Default user profile picture
    $defaultpfpimage = "/images/users/default/userdefaultprofile.png";

    foreach ($usernamewithoutwhitespaces as $username) {
        $username = trim($username);
        if ($username === '') continue;

        $username_safe = rawurlencode($username);

        $profileimage = "/images/users/{$username_safe}/";
        // file path to user image folder
        $profile_fs  = rtrim($_SERVER['DOCUMENT_ROOT'], "/") . $profileimage;

        // if no user profile folder is found use default pfp
        if (!is_dir($profile_fs)) {
            continue;
        }

        // find image file in user folder
        $files = glob($profile_fs . "*.{png,PNG,jpg,JPG,jpeg,JPEG,gif,GIF,webp,WEBP}", GLOB_BRACE);

        // if no user image is found use default pfp
        if (!$files || count($files) === 0) {
            continue;
        }

        // use first image in folder found
        $firstFile = basename($files[0]);
        return $profileimage . $firstFile;
    }

    return $defaultpfpimage;
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }


$username = $_SESSION['username'];
$successMsg = "";
$errorMsg = "";

$stmt = $conn->prepare("SELECT id, username, password, email, first_name, last_name, image_path, date_created, is_admin FROM profiles WHERE username = ? LIMIT 1");$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
  // if session exists but user row doesn't, reset session
  session_destroy();
  header("Location: login.php");
  exit();
}


function portfolio_dir_web($username) {
    $username = trim($username);
    $username_safe = rawurlencode($username);
    return "/images/users/{$username_safe}/portfolio/";
    
}
function portfolio_dir_fs($username) {
    return rtrim($_SERVER['DOCUMENT_ROOT'], "/") . portfolio_dir_web($username);
}


//handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    //logout session
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        header("Location: index.html");
        exit();
    }
    
    if ($action === 'update_basic') {
        $new_username = trim($_POST['username'] ?? '');
        $new_first_name = trim($_POST['first_name'] ?? '');
        $new_last_name = trim($_POST['last_name'] ?? '');
        
        if ($new_username === '' ) {
            $errorMsg = "Username cannot be empty";
        } elseif (strlen($new_username) > 50) {
            $errorMsg = "Username must be 50 characters or less.";
            
        } else {
            // check if the new username change i s different from database
            if ($new_username !== $user['username']) {
                $stmt = $conn->prepare("SELECT id FROM profiles WHERE username = ? LIMIT 1");
                $stmt->bind_param("s", $new_username);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($exists) {
                    $errorMsg = "That username is already taken.";
                }
            }
        
        if ($errorMsg === "") {
            //update the database with new entry
            $stmt = $conn->prepare("UPDATE profiles SET username = ?, first_name = ?, last_name = ? WHERE id = ? LIMIT 1");
            $stmt->bind_param("sssi", $new_username, $new_first_name, $new_last_name, $user['id']);
            
            if ($stmt->execute()) {
                $successMsg = "Basic profile info updated.";
                
                //if profile info is changed, update session an datbase
                if ($new_username !== $user['username']) {
                    $oldUser = $user['username'];
                    $_SESSION['username'] = $new_username;
                    
                    //rename user folder 
                    $oldDirWeb = "/images/users/" . rawurlencode($oldUser) . "/";
                    $newDirWeb = "/images/users/" . rawurlencode($new_username) . "/";
                    $oldDirFs  = rtrim($_SERVER['DOCUMENT_ROOT'], "/") . $oldDirWeb;
                    $newDirFs  = rtrim($_SERVER['DOCUMENT_ROOT'], "/") . $newDirWeb;
                    
                    if (is_dir($oldDirFs) && !is_dir($newDirFs)) {
                        @rename($oldDirFs, $newDirFs);
                    }
                }
            } else {
                $errorMsg = "User Update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

if ($action === 'change_email') {
    $new_email = trim($_POST['email'] ?? '');
    
    if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } else {
        // Ensure email unique and not used by someone else
        $stmt = $conn->prepare("SELECT id FROM profiles WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $new_email, $user['id']);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($exists) { 
            $errorMsg = "That email is already in use.";
            
        } else {
            $stmt = $conn->prepare("UPDATE profiles SET email = ? WHERE id = ? LIMIT 1");
            $stmt->bind_param("si", $new_email, $user['id']);
        
            if ($stmt->execute()) {
                $successMsg = "Email updated.";
                
            } else {
                $errorMsg = "Email update failed: " . $stmt->error;
            }
            $stmt -> close();
        }
    }
}
    
if ($action === 'change_password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new1    = (string)($_POST['new_password'] ?? '');
    $new2    = (string)($_POST['confirm_password'] ?? '');
    
    if ($current === '' || $new1 === '' || $new2 === '') {
      $errorMsg = "All password fields are required.";
    } elseif (!password_verify($current, $user['password'])) {
      $errorMsg = "Current password is incorrect.";
    } elseif ($new1 !== $new2) {
      $errorMsg = "New passwords do not match.";
    } elseif (strlen($new1) < 6) {
      $errorMsg = "New password must be at least 6 characters.";
    } else {
      $hash = password_hash($new1, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE profiles SET password = ? WHERE id = ? LIMIT 1");
      $stmt->bind_param("si", $hash, $user['id']);
      
      if ($stmt->execute()) {
          $successMsg = "Password updated.";
      } else {
          $errorMsg = "Password update failed: " . $stmt->error;
      }
      $stmt->close();
    }
}


if ($action === 'upload_portfolio') {
    if (!isset($_FILES['imageupload']) || $_FILES['imageupload']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Please choose an image to upload.";
    } else {
        $tmp = $_FILES['imageupload']['tmp_name'];
        $name = $_FILES['imageupload']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

        if (!in_array($ext, $allowed, true)) {
            $errorMsg = "Only PNG, JPG, JPEG, GIF, or WEBP files are allowed.";
        } else {
            $imageInfo = @getimagesize($tmp);

            if ($imageInfo === false || empty($imageInfo['mime'])) {
                $errorMsg = "Invalid image file.";
            } else {
                $mime = $imageInfo['mime'];
                $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

                if (!in_array($mime, $allowedMimes, true)) {
                    $errorMsg = "Invalid image MIME type.";
                } else {
                    $descriptors = trim($_POST['descriptors'] ?? '');

                    if ($descriptors === '') {
                        $errorMsg = "Please enter descriptors.";
                    } else {
                        $imageData = file_get_contents($tmp);

                        if ($imageData === false) {
                            $errorMsg = "Could not read uploaded file.";
                        } else {
                            $owner = (int)$user['id'];
                            $stmt = $conn->prepare("INSERT INTO portfolio (owner, file_type, descriptors, image) VALUES (?, ?, ?, ?)");
                            $null = NULL;
                            $stmt->bind_param("issb", $owner, $mime, $descriptors, $null);
                            $stmt->send_long_data(3, $imageData);

                            if ($stmt->execute()) {
                                $successMsg = "Image uploaded to portfolio.";
                            } else {
                                $errorMsg = "DB insert failed: " . $stmt->error;
                            }

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}


 
 
 
 
 
 
 
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id, username, password, email, first_name, last_name, date_created, is_admin FROM profiles WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

}


//display user profile image 
$profileimage = profile_img_for_user($user['username']);

$portfolioRows = [];
$owner = (int)$user['id'];

$stmt = $conn->prepare("SELECT work_id, file_type, image, descriptors FROM portfolio WHERE owner = ? ORDER BY work_id DESC");
$stmt->bind_param("i", $owner);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $portfolioRows[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Homepage </title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="style.css">
    <style>
    .container { padding: 18px 8% 0; }
    .profileHero { padding: 26px 8%; background: linear-gradient(135deg, #fff8ee, #f3ede4); }
    .profileTop { display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
    .avatar img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:1px solid #e5e7eb; background:#fafafa; }
    .infoCard { background:#fff; border:1px solid #e0f4ff; border-radius:14px; padding:18px; box-shadow:0 6px 18px rgba(0,0,0,0.06); margin: 18px 8% 22px; }
    .infoRow { display:grid; grid-template-columns: 180px 1fr; gap:10px; padding:8px 0; border-bottom:1px solid #f0f0f0; }
    .infoRow:last-child { border-bottom:none; }
    .label { font-weight:bold; color:#1f2937; }
    .val { color:#4b5563; overflow-wrap:anywhere; }

    label { display:block; margin-top:12px; font-weight:bold; color:#1f2937; }
    input { width:100%; padding:10px 12px; margin-top:6px; border:1px solid #d1d5db; border-radius:10px; background:#fff; }
    button { margin-top:14px; padding:12px 14px; border-radius:10px; cursor:pointer; border:none; font-weight:bold; }
    .btn-primary { background:#d97706; color:white; }
    .btn-secondary { background:white; color:#1f2937; border:1px solid #d1d5db; }

    .msg-ok { background:#eaffea; border:1px solid #b9e6b9; padding:10px; border-radius:10px; margin: 18px 8% 0; }
    .msg-err { background:#ffecec; border:1px solid #f2b5b5; padding:10px; border-radius:10px; margin: 18px 8% 0; }

    .imageuploadbox { display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-top:14px; }
    .imageuploadbox img { width:100%; aspect-ratio: 4/3; object-fit:cover; border-radius:12px; border:1px solid #e5e7eb; background:#fafafa; }
    @media (max-width: 900px) { .imageuploadbox { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .infoRow { grid-template-columns: 1fr; } .imageuploadbox { grid-template-columns: 1fr; } }
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
      <!-- <a href="businesses.php">Businesses</a> -->
      <a href="login.php">Login</a>
      <a href="logout.php">Logout</a>
  </nav>
</header>

<?php if ($successMsg): ?>
    <div class="msg-ok"><?php echo e($successMsg); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="msg-err"><?php echo e($errorMsg); ?></div>
<?php endif; ?>


<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
</div>

<section class="profileHero">
  <div class="profileTop">
    <div class="avatar">
      <img src="<?php echo htmlspecialchars($profileimage); ?>" alt="User image">
    </div>
    <div>
      <h2 style="margin:0; color:#111827;"><?php echo htmlspecialchars($user['username']); ?></h2>
      <p style="margin:6px 0 0; color:#6b7280;"><?php echo htmlspecialchars($user['email']); ?></p>
      <form method="post" style="margin-top:10px;">
          <input type="hidden" name="action" value="logout">
          <button class="btn-secondary" type="submit">Logout</button>
      </form>
    </div>
  </div>
</section>


<div class="infoCard">
  <h3 style="margin-top:0; color:#111827;">Portfolio Gallery</h3>

  <?php if (count($portfolioRows ?? []) === 0): ?>
    <p style="color:#ccd3e0; margin-top:10px;">No uploads yet. Upload your first piece below.</p>
  <?php else: ?>
    <div class="card">
      <?php foreach (array_slice($portfolioRows, 0, 12) as $row): ?>
        <div style="display:inline-block; margin:10px; text-align:center;">
          <img
            src="data:<?php echo e($row['file_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
            alt="Portfolio image"
            style="max-width:180px; display:block;"
          >
 <!-- showing current desctiptors -->
 <br>
   <td><?php echo htmlspecialchars($row['descriptors']); ?></td
 <br>
         <form method="post" action="update_description.php" style="margin-bottom:8px;" onsubmit="return confirm('Confirm Edit?');">
            <input type="hidden" name="work_id" value="<?php echo $row['work_id']; ?>">
            <textarea
              name="descriptors"
              rows="3"
              style="width:180px; resize:vertical;"
              placeholder="Enter description..."
            ><?php echo e($row['descriptors'] ?? ''); ?></textarea>
            <br><button class="btn" type="submit" style="margin-top:6px;">Update Description</button>
          </form>
          
   
   <br>
          <form method="post" action="delete_img.php" onsubmit="return confirm('Delete this image?');">
            <input type="hidden" name="work_id" value="<?php echo $row['work_id']; ?>">
            <button class="btn-danger" type="submit">Delete
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="infoCard">
  <h3 style="margin-top:0; color:#111827;">Upload Image</h3>
  <p style="color:#6b7280; margin-top:6px;">Uploaded images are shown here.</p>

  <form method="post" enctype="multipart/form-data" style="margin-top:14px;">
    <input type="hidden" name="action" value="upload_portfolio">
    <input type="file" name="imageupload" accept=".png,.jpg,.jpeg,.gif,.webp" required>

    <label for="descriptors">Descriptors</label>
    <textarea id="descriptors" name="descriptors" rows="5" style="width:100%;" required></textarea>

    <label for="imageupload">Select an image to upload, and give a description, before the submit button. In your eg,.gif,.webp" required>
    <button class="btn-primary" type="submit">Upload</button>
  </form>
</div>

<div class="infoCard">
  <h3 style="margin-top:0; color:#111827;">Account Information</h3>

  <div class="infoRow">
    <div class="label">ID</div>
    <div class="val"><?php echo htmlspecialchars($user['id']); ?></div>
  </div>

  <div class="infoRow">
    <div class="label">Username</div>
    <div class="val"><?php echo htmlspecialchars($user['username']); ?></div>
  </div>

  <div class="infoRow">
    <div class="label">Email</div>
    <div class="val"><?php echo htmlspecialchars($user['email']); ?></div>
  </div>

  <div class="infoRow">
    <div class="label">First Name</div>
    <div class="val"><?php echo htmlspecialchars($user['first_name']); ?></div>
  </div>

  <div class="infoRow">
    <div class="label">Last Name</div>
    <div class="val"><?php echo htmlspecialchars($user['last_name']); ?></div>
  </div>

  <div class="infoRow">
    <div class="label">Date Created</div>
    <div class="val"><?php echo htmlspecialchars($user['date_created']); ?></div>
  </div>
</div>

<div class="infoCard">
    <h3 style="margin-top:0; color:#111827;">Edit Basic Profile</h3>
    <p style="color:#6b7280; margin-top:6px;">Update your username, first name, and last name. (Email and password are changed below.)</p>
    
    
    <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="update_basic">
        <label>Username</label>
        <input type="text" name="username" value="<?php echo e($user['username']); ?>" required>

        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo e($user['first_name']); ?>">

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo e($user['last_name']); ?>">

        <button class="btn-primary" type="submit">Save Basic Info</button>
    </form>
</div>

<div class="infoCard">
    <h3 style="margin-top:0; color:#111827;">Change Email</h3>
    <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="change_email">
        
        <label>New Email</label>
        <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>
        
        <button class="btn-primary" type="submit">Update Email</button>
    </form>
</div> 

<div class="infoCard">
    <h3 style="margin-top:0; color:#111827;">Change Password</h3>
    <form method="post" autocomplete="on">
        
        <input type="hidden" name="action" value="change_password">
        <label>Current Password</label>
        <input type="password" name="current_password" required>

        <label>New Password</label>
        <input type="password" name="new_password" required>

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button class="btn-primary" type="submit">Update Password</button>
    </form>
</div>










</body>
</html>