<?php
require 'business_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$projects = [];
$error = '';
$success = '';


// Handle form submission (Post a Project)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_project') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget      = trim($_POST['budget'] ?? '');
    $deadline    = trim($_POST['deadline'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');

    if (!$title || !$category || !$description || !$contact) {
        $error = "Please fill in all required fields.";
    } elseif (isset($b_conn) && $b_conn instanceof mysqli) {
        $stmt = $b_conn->prepare("
            INSERT INTO projects (title, category, description, budget, deadline, contact, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            $error = "Failed to prepare insert: " . $b_conn->error;
        } else {
            $stmt->bind_param("ssssss", $title, $category, $description, $budget, $deadline, $contact);

            if ($stmt->execute()) {
                $success = "Your project has been posted successfully!";
            } else {
                $error = "Failed to post project: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "Database connection is not available.";
    }
}if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_project') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget      = trim($_POST['budget'] ?? '');
    $deadline    = trim($_POST['deadline'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');

    if (!$title || !$category || !$description || !$contact) {
        $error = "Please fill in all required fields.";
    } elseif (isset($b_conn) && $b_conn instanceof mysqli) {
        $stmt = $b_conn->prepare("
            INSERT INTO projects (title, category, description, budget, deadline, contact, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt) {
            $error = "Failed to prepare insert: " . $b_conn->error;
        } else {
            $stmt->bind_param("ssssss", $title, $category, $description, $budget, $deadline, $contact);

            if ($stmt->execute()) {
                $success = "Your project has been posted successfully!";
            } else {
                $error = "Failed to post project: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "Database connection is not available.";
    }
}


// Fetch existing projects
$filter_category = trim($_GET['category'] ?? '');
if (isset($b_conn) && $b_conn instanceof mysqli) {
    if ($filter_category) {
        $stmt = $b_conn->prepare("SELECT * FROM projects WHERE category = ? ORDER BY created_at DESC");
        if (!$stmt) {
            $error = "Failed to prepare query: " . $b_conn->error;
        } else {
            $stmt->bind_param("s", $filter_category);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                $projects = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            } else {
                $error = "Failed to load projects: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $res = $b_conn->query("SELECT * FROM projects ORDER BY created_at DESC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $projects[] = $row;
            }
        } else {
            $error = "Failed to load projects: " . $b_conn->error;
        }
    }
}



$categories = ['Illustration', 'Branding', 'Mural', 'Social Media', 'Photography', 'Web Design', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Projects – Connect Canvas</title>
  <link rel="stylesheet" href="style.css">
  <style>
/* ── Page Hero ── */
.page-hero {
  background: linear-gradient(135deg, #EAF4FB, #D4EED4);
  color: #333;
  padding: 60px 40px 50px;
  text-align: center;
}

.page-hero h1 {
  font-size: 2.4rem;
  margin-bottom: 12px;
  color: #3F75E0;
}

.page-hero p {
  font-size: 1.05rem;
  opacity: .85;
  max-width: 600px;
  margin: 0 auto;
  color: #555;
}

/* ── Filter bar */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  padding: 28px 40px 10px;
  background: #F0F8FF;
  border-bottom: 1px solid #BDE3F5;
}

.filter-bar a {
  padding: 7px 18px;
  border-radius: 20px;
  border: 1px solid #BDE3F5;
  text-decoration: none;
  font-size: .875rem;
  color: #3EA6DE;
  transition: all .2s;
  background: #fff;
}

.filter-bar a:hover,
.filter-bar a.active {
  background: #3EA6DE;
  color: #fff;
  border-color: #3EA6DE;
}

/* ── Project grid ── */
.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  padding: 40px;
  max-width: 1200px;
  margin: 0 auto;
}

.project-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
  padding: 26px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: transform .2s, box-shadow .2s;
  border: 1px solid #BDE3F5;
}

.project-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(62,166,222,.2);
}

/* Badge  */
.project-card .badge {
  display: inline-block;
  background: #EAF4FB;
  color: #3EA6DE;
  font-size: .75rem;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 20px;
  width: fit-content;
  border: 1px solid #BDE3F5;
}

/* Title */
.project-card h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #3F75E0;
}

/* Description */
.project-card p {
  font-size: .9rem;
  color: #555;
  margin: 0;
  line-height: 1.5;
  flex: 1;
}

/* Meta info */
.project-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: .8rem;
  color: #666;
  border-top: 1px solid #EAF4FB;
  padding-top: 12px;
  margin-top: 4px;
}

.project-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Apply button  */
.btn-apply {
  margin-top: 4px;
  display: inline-block;
  padding: 9px 20px;
  background: #3EA6DE;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-size: .875rem;
  font-weight: 600;
  text-align: center;
  transition: background .2s;
}

.btn-apply:hover {
  background: #3F75E0;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #888;
  grid-column: 1 / -1;
}

.empty-state h3 {
  color: #444;
  margin-bottom: 8px;
}

/* Post section */
.post-section {
  background: #F0F8FF;
  padding: 60px 40px;
  border-top: 1px solid #BDE3F5;
}

.post-section h2 {
  text-align: center;
  margin-bottom: 8px;
  color: #3EA6DE;
}

.post-section .section-text {
  text-align: center;
  margin-bottom: 36px;
  color: #555;
}

/* Form */
.form-card {
  max-width: 700px;
  margin: 0 auto;
  background: #fff;
  border-radius: 14px;
  padding: 40px;
  box-shadow: 0 4px 20px rgba(0,0,0,.08);
  border: 1px solid #BDE3F5;
}

.form-group label {
  font-size: .875rem;
  font-weight: 600;
  color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 10px 14px;
  border: 1px solid #C8C8DE;
  border-radius: 8px;
  font-size: .9rem;
  font-family: inherit;
  background: #fafafa;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3EA6DE;
  background: #fff;
}

.form-submit button {
  padding: 13px 40px;
  background: #3EA6DE;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.form-submit button:hover {
  background: #3F75E0;
}

/* Alerts */
.alert-success {
  background: #D4EDDA;
  color: #155724;
  border: 1px solid #C3E6CB;
}

.alert-error {
  background: #F8D7DA;
  color: #721c24;
  border: 1px solid #F5C6CB;
}

/* Responsive */
@media (max-width: 600px) {
  .projects-grid {
    padding: 20px;
  }

  .post-section {
    padding: 40px 20px;
  }

  .form-card {
    padding: 24px;
  }
}
</style>
</head>
<body>

  <!-- ── Header  ── -->
  <header>
    <div class="logo">Connect Canvas</div>
    <nav>
      <a href="index.html">Home</a>
      <a href="profile.php">Profile</a>
      <a href="portfolios.php">Portfolios</a>
      <a href="projects.php" class="active">Projects</a>
      <!-- <a href="businesses.php">Businesses</a> -->
      <a href="login.php">Login</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <!-- ── Page Hero ── -->
  <section class="page-hero">
    <h1>Open Projects</h1>
    <p>Browse creative opportunities posted by local businesses and nonprofits, or post your own project to find the right artist.</p>
  </section>

  <!-- ── Category Filter ── -->
  <div class="filter-bar">
    <a href="projects.php" class="<?= !$filter_category ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
      <a href="projects.php?category=<?= urlencode($cat) ?>"
         class="<?= $filter_category === $cat ? 'active' : '' ?>">
        <?= htmlspecialchars($cat) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Alerts ── -->
  <?php if ($success): ?>
    <div class="alert alert-success" style="margin: 24px auto 0; max-width: 900px;">
      ✅ <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error" style="margin: 24px auto 0; max-width: 900px;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- ── Projects Grid ── -->
  <div class="projects-grid">
    <?php if (empty($projects)): ?>
      <div class="empty-state">
        <h3>No projects yet<?= $filter_category ? " in \"$filter_category\"" : "" ?></h3>
        <p>Be the first to post a project below!</p>
      </div>
    <?php else: ?>
      <?php foreach ($projects as $p): ?>
        <div class="project-card">
          <span class="badge"><?= htmlspecialchars($p['category']) ?></span>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
          <div class="project-meta">
            <?php if (!empty($p['budget'])): ?>
              <span>💰 <?= htmlspecialchars($p['budget']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['deadline'])): ?>
              <span>📅 <?= htmlspecialchars($p['deadline']) ?></span>
            <?php endif; ?>
            <span>✉️ <?= htmlspecialchars($p['contact']) ?></span>
          </div>
          <a href="apply.php?project_id=<?= (int)$p['id'] ?>" class="btn-apply">Apply</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── Post a Project Form ── -->
  <section class="post-section">
    <h2>Post a Project</h2>
    <p class="section-text">Looking for creative talent? Fill out the form below and connect with local artists.</p>

    <div class="form-card">
      <form method="POST" action="projects.php">
        <input type="hidden" name="action" value="post_project">
        <div class="form-grid">

          <div class="form-group">
            <label for="title">Project Title <span style="color:red">*</span></label>
            <input type="text" id="title" name="title" placeholder="e.g. Logo Design for Coffee Shop"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label for="category">Category <span style="color:red">*</span></label>
            <select id="category" name="category" required>
              <option value="">— Select a category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                  <?= $cat ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group full">
            <label for="description">Project Description <span style="color:red">*</span></label>
            <textarea id="description" name="description" placeholder="Describe what you need, the style, deliverables, and any other details..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="budget">Budget (optional)</label>
            <input type="text" id="budget" name="budget" placeholder="e.g. $200 or Unpaid/Experience"
                   value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="deadline">Deadline (optional)</label>
            <input type="date" id="deadline" name="deadline"
                   value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
          </div>

          <div class="form-group full">
            <label for="contact">Contact Email <span style="color:red">*</span></label>
            <input type="email" id="contact" name="contact" placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
          </div>

        </div>
        <div class="form-submit">
          <button type="submit">Post Project</button>
        </div>
      </form>
    </div>
  </section>

</body>
</html>