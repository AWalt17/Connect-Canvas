<?php
require 'initial.php';
//start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$profiles = [];
$result = $conn->query("SELECT id, username, first_name, last_name, date_created FROM profiles ORDER BY username ");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profiles[] = $row;
        
    }
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



?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Midwest Art Connect | Portfolios</title>
  <link rel="stylesheet" href="style.css">
  <style>
  .modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    padding-top: 100px; /* Location of the box */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
  }

.modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
  }


.close {
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
</style>

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
  <h1>Portfolios</h1>
  <p>Browse Midwest artists. Search and Use Filters find and connect with artists</p>
</section>

<section class="section">
  <div class="toolbar">
    <input type="search" placeholder="Search artists" aria-label="Search portfolios">
    <select aria-label="Filter by location">
      <option>Location (All)</option>
      <option>Milwaukee, WI</option>
      <option>Madison, WI</option>
      <option>Eau Claire, WI</option>
      <option>Minneapolis, MN</option>
      <option>Detroit, MI</option>
      <option>Chicago, IL</option>
    </select>
    <select aria-label="Sort portfolios">
      <option>Sort: Recommended</option>
      <option>Sort: Newest</option>
      <option>Sort: Most Viewed</option>
    </select>
    <a class="btn btn-secondary" href="portfolios.php">Reset</a>
  </div>

  <div class="chip-row" aria-label="Popular filters">
    <span class="chip">Illustration</span>
    <span class="chip">Logo Design</span>
    <span class="chip">Website Design</span>
    <span class="chip">Branding design</span>
    <span class="chip">Photography</span>
    <span class="chip">3D Modeling</span>
  </div>
</section>




<div id="myModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>

    <h2 id="modalUsername" style="margin-top:0;"></h2>
    <p id="modalMeta" style="color:#6b7280; margin-top:6px;"></p>
    <div id="modalPreview"></div>
  </div>
</div>



<!-- section for pulling up list of artists with previews-->
<section>
  <h2>Featured Artists</h2>

  <?php if (count($profiles) === 0): ?>
    <p class="section-text">No Profiles Found to Display</p>
  <?php else: ?>
    <div class="cards">
      <?php foreach ($profiles as $profile): ?>
        <?php
          $u   = $profile['username'];
          $img = profile_img_for_user($u);
          $id  = (int)$profile['id'];

          $first  = $profile['first_name'] ?? '';
          $last   = $profile['last_name'] ?? '';
          $joined = $profile['date_created'] ?? '';

          $get = "SELECT file_type, image, owner
                  FROM portfolio
                  where owner = $id
                  LIMIT 3";
          $preview = mysqli_query($conn, $get);
        ?>

        <!-- CHANGED: make card clickable + add data-* for modal -->
        <div class="card profile-card"
             role="button"
             tabindex="0"
             data-username="<?php echo htmlspecialchars($u, ENT_QUOTES); ?>"
             data-first="<?php echo htmlspecialchars($first, ENT_QUOTES); ?>"
             data-last="<?php echo htmlspecialchars($last, ENT_QUOTES); ?>"
             data-joined="<?php echo htmlspecialchars($joined, ENT_QUOTES); ?>"
             data-avatar="<?php echo htmlspecialchars($img, ENT_QUOTES); ?>">

          <div style="display:flex; gap:12px; align-items:flex-start;">
            <img
              src="<?php echo htmlspecialchars($img); ?>"
              alt="Profile image"
              style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb;background:#fafafa;"
            >

            <div style="flex:1;">
              <h3 style="margin:0;"><?php echo htmlspecialchars($u); ?></h3>
              <p style="margin:4px 0 10px; color:#6b7280; font-size:14px;">Artist Profile</p>

              <?php if ($preview && mysqli_num_rows($preview) > 0): ?>
                <div class="preview-strip" style="display:flex; gap:8px; flex-wrap:nowrap;">
                  <?php while ($row = mysqli_fetch_assoc($preview)): ?>
                    <img
                      src="data:<?php echo htmlspecialchars($row['file_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
                      alt="Portfolio image"
                      style="width:33.33%; max-width:180px; height:auto; border-radius:8px; object-fit:cover;"
                    >
                  <?php endwhile; ?>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>

      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
var modal = document.getElementById("myModal");
var span  = document.getElementsByClassName("close")[0];

var modalUsername = document.getElementById("modalUsername");
var modalMeta     = document.getElementById("modalMeta");
var modalPreview  = document.getElementById("modalPreview");

function openProfileModal(card) {
  var username = card.dataset.username || "";
  var first    = card.dataset.first || "";
  var last     = card.dataset.last || "";
  var joined   = card.dataset.joined || "";

  modalUsername.textContent = username;

  var fullName = (first + " " + last).trim();
  var metaParts = [];
  if (fullName) metaParts.push(fullName);
  if (joined) metaParts.push("Joined: " + joined);
  modalMeta.textContent = metaParts.join(" • ");

  // Copy the preview images from the clicked card into the modal
  modalPreview.innerHTML = "";
  var strip = card.querySelector(".preview-strip");
  if (strip) {
    modalPreview.innerHTML = strip.innerHTML;
  } else {
    modalPreview.innerHTML = "<p style='color:#6b7280; margin-top:10px;'>No portfolio preview images available.</p>";
  }

  modal.style.display = "block";
}

// Click and keyboard support
document.querySelectorAll(".profile-card").forEach(function(card) {
  card.addEventListener("click", function() {
    openProfileModal(card);
  });

  card.addEventListener("keydown", function(e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      openProfileModal(card);
    }
  });
});

// Close handlers
span.onclick = function() {
  modal.style.display = "none";
}

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>


<footer>
  Midwest Art Connect • Portfolios
</footer>

</body>
</html>