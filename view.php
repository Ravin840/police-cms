<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── DB Configuration ──
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'crime_db';

// connect
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('<div style="color:red">DB connection failed: ' . htmlspecialchars($conn->connect_error) . '</div>');
}

// ── Discover table columns & primary key ──
$table      = 'cases';
$descRes    = $conn->query("DESCRIBE `{$table}`");
$columns    = [];
while ($col = $descRes->fetch_assoc()) {
    $columns[] = $col['Field'];
}
if (empty($columns)) {
    die('<div style="color:red">Table `' . htmlspecialchars($table) . '` has no columns.</div>');
}
$pk = $columns[0];  // assume first column is the key

// ── Determine mode: list vs detail ──
$idParam = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idParam > 0) {
    // detail
    $stmt   = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$pk}` = ?");
    $stmt->bind_param('i', $idParam);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    // list
    $listRes = $conn->query("
      SELECT *
        FROM `{$table}`
       ORDER BY `{$pk}` DESC
    ");
    $rows    = $listRes->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Police CMS – Cases</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="view.php">Police CMS</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item">
        <a class="nav-link<?= $idParam===0 ? ' active':''?>" href="view.php">
          All Cases
        </a>
      </li>
    </ul>
  </div>
</nav>

<main class="container my-5 flex-fill">
  <?php if ($idParam > 0): ?>
    <?php if ($detail): ?>
      <h2>Case #<?= htmlspecialchars($detail[$pk]) ?></h2>
      <div class="card mb-3">
        <div class="card-body">
          <dl class="row">
            <?php foreach ($detail as $field => $val): ?>
              <dt class="col-sm-3 text-capitalize"><?= str_replace('_',' ',$field) ?></dt>
              <dd class="col-sm-9"><?= nl2br(htmlspecialchars($val)) ?></dd>
            <?php endforeach; ?>
          </dl>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">Case not found (ID <?= $idParam ?>).</div>
    <?php endif; ?>
    <a href="view.php" class="btn btn-secondary">← Back to All Cases</a>

  <?php else: ?>
    <h2>All Reported Cases</h2>
    <?php if (!empty($rows)): ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <?php foreach ($columns as $col): ?>
                <th class="text-capitalize"><?= str_replace('_',' ',$col) ?></th>
              <?php endforeach; ?>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <?php foreach ($columns as $col): ?>
                  <td><?= htmlspecialchars($r[$col]) ?></td>
                <?php endforeach; ?>
                <td>
                  <a
                    href="view.php?id=<?= urlencode($r[$pk]) ?>"
                    class="btn btn-sm btn-outline-primary"
                  >View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No cases have been reported yet.</div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3 mt-auto">
  &copy; 2025 Police CMS
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
