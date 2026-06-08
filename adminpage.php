<?php
require 'initial.php';
//start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!isset($_SESSION['username']) || (int)($_SESSION['is_admin'] ?? 0) !== 1) {
  header("Location: login.php");
  exit();
}

$successMsg = "";
$errorMsg   = "";

function profile_img_for_user($username) {
  $username = trim($username);

  // Try exact username + folder-safe variant (spaces -> underscores)
  $usernamewithoutwhitespaces = [
    $username,
    preg_replace('/[^A-Za-z0-9_-]+/', '_', str_replace(' ', '_', $username)),
  ];

  // Default user profile picture
  $defaultpfpimage = "/images/users/default/userdefaultprofile.png";

  foreach ($usernamewithoutwhitespaces as $candidate) {
    $candidate = trim($candidate);
    if ($candidate === '') continue;

    $username_safe = rawurlencode($candidate);

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

// detect portfolio ordering column safely
$portfolioOrderCol = null;
$colRes = $conn->query("SHOW COLUMNS FROM portfolio");
$cols = [];
if ($colRes) {
  while ($r = $colRes->fetch_assoc()) $cols[] = $r['Field'];
}
foreach (['work_id','id','portfolio_id','created_at','date_created'] as $cand) {
  if (in_array($cand, $cols, true)) { $portfolioOrderCol = $cand; break; }
}
if (!$portfolioOrderCol) $portfolioOrderCol = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'admin_update_user') {
    $user_id    = (int)($_POST['user_id'] ?? 0);
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');

    if ($user_id <= 0) {
      $errorMsg = "Invalid user id.";
    } elseif ($username === '') {
      $errorMsg = "Username cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errorMsg = "Please enter a valid email.";
    } else {
      // uniqueness checks
      $stmt = $conn->prepare("SELECT id FROM profiles WHERE username = ? AND id <> ? LIMIT 1");
      $stmt->bind_param("si", $username, $user_id);
      $stmt->execute();
      $existsU = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $stmt = $conn->prepare("SELECT id FROM profiles WHERE email = ? AND id <> ? LIMIT 1");
      $stmt->bind_param("si", $email, $user_id);
      $stmt->execute();
      $existsE = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if ($existsU) {
        $errorMsg = "That username is already taken.";
      } elseif ($existsE) {
        $errorMsg = "That email is already in use.";
      } else {
        $stmt = $conn->prepare("UPDATE profiles SET username = ?, email = ?, first_name = ?, last_name = ? WHERE id = ? LIMIT 1");
        $stmt->bind_param("ssssi", $username, $email, $first_name, $last_name, $user_id);

        if ($stmt->execute()) {
          $successMsg = "User updated successfully.";
        } else {
          $errorMsg = "Update failed: " . $stmt->error;
        }
        $stmt->close();
      }
    }
  }

  if ($action === 'admin_delete_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $confirm = (string)($_POST['confirm_delete'] ?? '');

    if ($user_id <= 0) {
      $errorMsg = "Invalid user id.";
    } elseif ($confirm !== 'YES') {
      $errorMsg = "Delete not confirmed.";
    } else {
      // delete portfolio rows first
      $stmt = $conn->prepare("DELETE FROM portfolio WHERE owner = ?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->close();

      // delete profile
      $stmt = $conn->prepare("DELETE FROM profiles WHERE id = ? LIMIT 1");
      $stmt->bind_param("i", $user_id);

      if ($stmt->execute()) {
        $successMsg = "User deleted.";
      } else {
        $errorMsg = "Delete failed: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// load all users
$profiles = [];
$res = $conn->query("SELECT id, username, email, first_name, last_name, date_created, is_admin FROM profiles ORDER BY username");
if ($res) {
  while ($row = $res->fetch_assoc()) $profiles[] = $row;
}

// project applications table
$appTable = null;
$appsRows = [];
$appsColumns = [];

$chk = $conn->query("SHOW TABLES LIKE 'project_applications'");
if ($chk && $chk->num_rows > 0) $appTable = 'project_applications';

if (!$appTable) {
  $chk = $conn->query("SHOW TABLES LIKE 'projects'");
  if ($chk && $chk->num_rows > 0) $appTable = 'projects';
}

if ($appTable) {
  $cRes = $conn->query("SHOW COLUMNS FROM `$appTable`");
  if ($cRes) while ($r = $cRes->fetch_assoc()) $appsColumns[] = $r['Field'];

  $orderCol = null;
  foreach (['id','application_id','work_id','created_at','date_created'] as $cand) {
    if (in_array($cand, $appsColumns, true)) { $orderCol = $cand; break; }
  }
  if (!$orderCol && count($appsColumns) > 0) $orderCol = $appsColumns[0];

  $q = "SELECT * FROM `$appTable`";
  if ($orderCol) $q .= " ORDER BY `$orderCol` DESC";
  $q .= " LIMIT 25";

  $rRes = $conn->query($q);
  if ($rRes) while ($r = $rRes->fetch_assoc()) $appsRows[] = $r;
}

$adminName = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .msg-ok { background:#eaffea; border:1px solid #b9e6b9; padding:10px; border-radius:10px; margin: 18px 8% 0; }
    .msg-err { background:#ffecec; border:1px solid #f2b5b5; padding:10px; border-radius:10px; margin: 18px 8% 0; }
    .admin-section { padding: 26px 8% 60px; }
    .cards { display:grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    @media (max-width: 992px){ .cards { grid-template-columns: 1fr; } }

    /* The Modal (background) */
    .modal {
      display: none; /* Hidden by default */
      position: fixed; /* Stay in place */
      z-index: 999; /* Sit on top */
      padding-top: 100px; /* Location of the box */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%; /* Full height */
      overflow: auto; /* Enable scroll if needed */
      background-color: rgb(0,0,0); /* Fallback color */
      background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content */
    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border: 1px solid #888;
      width: 90%;
      max-width: 980px;
      border-radius: 14px;
    }

    /* The Close Button */
    .close {
      color: #aaaaaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover,
    .close:focus {
      color: #000;
      text-decoration: none;
      cursor: pointer;
    }

    .modal-head { display:flex; gap:14px; align-items:center; flex-wrap:wrap; padding-bottom:12px; border-bottom:1px solid #e5e7eb; margin-bottom:14px; }
    .modal-avatar { width:86px; height:86px; border-radius:50%; object-fit:cover; border:1px solid #e5e7eb; background:#fafafa; }
    .modal-gallery { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px; margin-top:10px; }
    .modal-gallery img { width:100%; height:160px; object-fit:cover; border-radius:12px; border:1px solid #e5e7eb; background:#fafafa; display:block; }

    .profile-card { cursor:pointer; }
    .profile-card:focus { outline:2px solid #d97706; outline-offset:2px; }

    .admin-form-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
    .admin-form-grid label { font-weight: bold; color:#1f2937; display:block; margin-bottom:6px; }
    .admin-form-grid input { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; background:#fff; }
    .admin-form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top: 14px; }

    .btn-primary { background:#d97706; color:#fff; border:none; padding:12px 14px; border-radius:10px; cursor:pointer; font-weight:bold; }
    .btn-secondary { background:#fff; color:#1f2937; border:1px solid #d1d5db; padding:12px 14px; border-radius:10px; cursor:pointer; font-weight:bold; }
    .btn-danger { background:#b91c1c; color:#fff; border:none; padding:12px 14px; border-radius:10px; cursor:pointer; font-weight:bold; }

    .welcome { display:block; }
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
    <a href="logout.php">Logout</a>
  </nav>
</header>

<?php if ($successMsg): ?>
  <div class="msg-ok"><?php echo e($successMsg); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="msg-err"><?php echo e($errorMsg); ?></div>
<?php endif; ?>

<div id="welcomeModal" class="modal welcome">
  <!-- Modal content -->
  <div class="modal-content">
    <span class="close" id="welcomeClose">&times;</span>
    <h2 style="margin-top:0; color:#111827;">Hello Admin, <?php echo e($adminName); ?> 👋</h2>
    <p style="color:#6b7280; margin-top:6px;">Click any user card to view details, edit, or delete.</p>
    <button class="btn-primary" type="button" id="welcomeContinue">Continue</button>
  </div>
</div>

<!-- section for pulling up list of artists with previews-->
<section class="admin-section">
  <h2 style="color:#111827; margin-bottom:10px;">All Registered Users</h2>

  <div class="cards">
    <?php foreach ($profiles as $p): ?>
      <?php
        $u = $p['username'];
        $id = (int)$p['id'];
        $avatar = profile_img_for_user($u);

        $previewRows = [];
        $q = "SELECT file_type, image FROM portfolio WHERE owner = ? ";
        if ($portfolioOrderCol) $q .= "ORDER BY `$portfolioOrderCol` DESC ";
        $q .= "LIMIT 8";

        $stmt = $conn->prepare($q);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $previewRows[] = $row;
        $stmt->close();
      ?>

      <div class="card profile-card"
           role="button"
           tabindex="0"
           data-user-id="<?php echo $id; ?>"
           data-username="<?php echo e($p['username']); ?>"
           data-email="<?php echo e($p['email']); ?>"
           data-first="<?php echo e($p['first_name']); ?>"
           data-last="<?php echo e($p['last_name']); ?>"
           data-created="<?php echo e($p['date_created']); ?>"
           data-avatar="<?php echo e($avatar); ?>">

        <div style="display:flex; gap:12px; align-items:flex-start;">
          <img src="<?php echo e($avatar); ?>"
               alt="Profile image"
               style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;background:#fafafa;">

          <div style="flex:1;">
            <h3 style="margin:0;"><?php echo e($p['username']); ?></h3>
            <p style="margin:4px 0 10px; color:#6b7280; font-size:14px;">User Profile</p>

            <?php if (count($previewRows) > 0): ?>
              <div class="preview-strip" style="display:flex; gap:8px; flex-wrap:nowrap;">
                <?php foreach (array_slice($previewRows, 0, 3) as $row): ?>
                  <img
                    src="data:<?php echo e($row['file_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
                    alt="Portfolio preview"
                    style="width:33.33%; max-width:180px; height:auto; border-radius:8px; object-fit:cover;"
                  >
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="artist-gallery" hidden>
              <?php foreach ($previewRows as $row): ?>
                <img
                  src="data:<?php echo e($row['file_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
                  alt="Portfolio image"
                >
              <?php endforeach; ?>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<div id="userModal" class="modal">
  <!-- Modal content -->
  <div class="modal-content">
    <span class="close" id="userClose">&times;</span>

    <div class="modal-head">
      <img id="modalAvatar" class="modal-avatar" src="" alt="User profile image">
      <div>
        <h2 id="modalTitle" style="margin:0; color:#111827;"></h2>
        <p id="modalSub" style="margin:4px 0 0; color:#6b7280;"></p>
      </div>
    </div>

    <h3 style="margin:0; color:#111827;">User Details</h3>
    <p id="modalDetails" style="color:#6b7280; margin-top:6px;"></p>

    <div id="modalGallery" class="modal-gallery"></div>

    <h3 style="margin-top:18px; color:#111827;">Edit User</h3>

    <form method="post">
      <input type="hidden" name="action" value="admin_update_user">
      <input type="hidden" name="user_id" id="formUserId">

      <div class="admin-form-grid">
        <div>
          <label>Username</label>
          <input type="text" name="username" id="formUsername" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email" id="formEmail" required>
        </div>
        <div>
          <label>First Name</label>
          <input type="text" name="first_name" id="formFirst">
        </div>
        <div>
          <label>Last Name</label>
          <input type="text" name="last_name" id="formLast">
        </div>
      </div>

      <div class="admin-form-actions">
        <button class="btn-primary" type="submit">Save Changes</button>
      </div>
    </form>

    <div class="admin-form-actions">
      <form method="post" id="deleteForm" style="display:inline;">
        <input type="hidden" name="action" value="admin_delete_user">
        <input type="hidden" name="user_id" id="delUserId">
        <input type="hidden" name="confirm_delete" value="YES">
        <button class="btn-danger" type="submit" id="deleteBtn">Delete User</button>
      </form>
    </div>

  </div>
</div>

<section class="admin-section" style="padding-top:0;">
  <h2 style="color:#111827; margin-bottom:10px;">
    <?php echo $appTable === 'project_applications' ? "Submitted Project Applications" : ($appTable === 'projects' ? "Submitted Projects" : "Project Applications"); ?>
  </h2>

  <?php if (!$appTable): ?>
    <p style="color:#6b7280;">No project applications/projects table found yet.</p>
  <?php else: ?>
    <div class="card" style="overflow:auto;">
      <p style="color:#6b7280; margin-top:0;">Showing latest 25 rows from <code><?php echo e($appTable); ?></code>.</p>

      <?php if (count($appsRows) === 0): ?>
        <p style="color:#6b7280;">No rows found.</p>
      <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <?php foreach ($appsColumns as $c): ?>
                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;"><?php echo e($c); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appsRows as $r): ?>
              <tr>
                <?php foreach ($appsColumns as $c): ?>
                  <td style="padding:8px; border-bottom:1px solid #f0f0f0; color:#4b5563;"><?php echo e($r[$c] ?? ''); ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<script>
var welcomeModal = document.getElementById("welcomeModal");
var welcomeClose = document.getElementById("welcomeClose");
var welcomeContinue = document.getElementById("welcomeContinue");

welcomeClose.onclick = function() { welcomeModal.style.display = "none"; }
welcomeContinue.onclick = function() { welcomeModal.style.display = "none"; }

var modal = document.getElementById("userModal");
var closeBtn = document.getElementById("userClose");

var modalAvatar = document.getElementById("modalAvatar");
var modalTitle = document.getElementById("modalTitle");
var modalSub = document.getElementById("modalSub");
var modalDetails = document.getElementById("modalDetails");
var modalGallery = document.getElementById("modalGallery");

var formUserId = document.getElementById("formUserId");
var formUsername = document.getElementById("formUsername");
var formEmail = document.getElementById("formEmail");
var formFirst = document.getElementById("formFirst");
var formLast = document.getElementById("formLast");

var delUserId = document.getElementById("delUserId");
var deleteBtn = document.getElementById("deleteBtn");

function openUserModal(card) {
  var id = card.dataset.userId || "";
  var username = card.dataset.username || "";
  var email = card.dataset.email || "";
  var first = card.dataset.first || "";
  var last = card.dataset.last || "";
  var created = card.dataset.created || "";
  var avatar = card.dataset.avatar || "";

  modalAvatar.src = avatar;
  modalTitle.textContent = username;
  modalSub.textContent = email;

  var fullName = (first + " " + last).trim();
  if (!fullName) fullName = "(not set)";
  modalDetails.textContent = "ID: " + id + " • Name: " + fullName + " • Created: " + created;

  modalGallery.innerHTML = "";
  var hiddenGallery = card.querySelector(".artist-gallery");
  if (hiddenGallery) {
    hiddenGallery.querySelectorAll("img").forEach(function(img){
      modalGallery.appendChild(img.cloneNode(true));
    });
  }

  formUserId.value = id;
  formUsername.value = username;
  formEmail.value = email;
  formFirst.value = first;
  formLast.value = last;

  delUserId.value = id;

  modal.style.display = "block";
}

document.querySelectorAll(".profile-card").forEach(function(card){
  card.addEventListener("click", function(){ openUserModal(card); });
  card.addEventListener("keydown", function(e){
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      openUserModal(card);
    }
  });
});

closeBtn.onclick = function() { modal.style.display = "none"; }

window.onclick = function(event) {
  if (event.target == modal) modal.style.display = "none";
  if (event.target == welcomeModal) welcomeModal.style.display = "none";
}

deleteBtn.addEventListener("click", function(e){
  var ok = confirm("Are you sure you want to permanently delete this user? This cannot be undone.");
  if (!ok) e.preventDefault();
});
</script>

</body>
</html>