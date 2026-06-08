<?php
require 'portfolio.php';

$owner = isset($_GET['owner']) ? (int)$_GET['owner'] : 0;

$sql = "SELECT file_type, image
        FROM portfolio
        WHERE owner = ?
        ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $owner);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$portfolioRows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $portfolioRows[] = $row;
}

function e($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="infoCard">
  <h3 style="margin-top:0; color:#111827;">Portfolio Gallery</h3>

  <?php if (count($portfolioRows) === 0): ?>
    <p style="color:#6b7280; margin-top:10px;">No uploads yet. Upload your first piece below.</p>
  <?php else: ?>
    <div class="portfolioGrid">
      <?php foreach (array_slice($portfolioRows, 0, 12) as $row): ?>
        <img
          src="data:<?php echo e($row['file_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
          alt="Portfolio image"
        >
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>