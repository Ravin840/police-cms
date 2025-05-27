<?php
session_start();
require_once 'config.php';

// Only admins can view logs
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();

// Pagination settings
$perPage = 20;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Filters
$fromDate = $_GET['from_date'] ?? '';
$toDate   = $_GET['to_date']   ?? '';
$username = trim($_GET['username'] ?? '');

// Build WHERE clauses and parameters
$whereClauses = [];
$bindTypes    = '';
$bindValues   = [];

if ($fromDate) {
    $whereClauses[] = 'l.login_time >= ?';
    $bindTypes    .= 's';
    $bindValues[]  = $fromDate . ' 00:00:00';
}
if ($toDate) {
    $whereClauses[] = 'l.login_time <= ?';
    $bindTypes    .= 's';
    $bindValues[]  = $toDate . ' 23:59:59';
}
if ($username) {
    $whereClauses[] = 's.username LIKE ?';
    $bindTypes    .= 's';
    $bindValues[]  = '%' . $username . '%';
}

$whereSql = $whereClauses
  ? 'WHERE ' . implode(' AND ', $whereClauses)
  : '';

// 1) Get total count
$countSql = "
  SELECT COUNT(*) AS cnt
    FROM login_logs l
    JOIN staff s ON l.user_id = s.id
  $whereSql
";
$stmt = $db->prepare($countSql);
if ($bindValues) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$totalPages = ceil($total / $perPage);

// 2) Fetch paginated logs
$dataSql = "
  SELECT l.login_time, s.username, s.role, l.ip_address
    FROM login_logs l
    JOIN staff s ON l.user_id = s.id
  $whereSql
  ORDER BY l.login_time DESC
  LIMIT ? OFFSET ?
";
$stmt = $db->prepare($dataSql);

// Combine filter params + pagination params
$allTypes  = $bindTypes . 'ii';
$allValues = array_merge($bindValues, [$perPage, $offset]);

$stmt->bind_param($allTypes, ...$allValues);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Audit Logs | Police NSW CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <style>
    body { background: #f8f9fa; }
    .card { border-radius: .75rem; }
    .pagination { justify-content: center; }
  </style>
</head>
<body class="p-4">
  <div class="container">
    <h2 class="mb-4">Audit Logs</h2>

    <!-- Filter Form -->
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" name="from_date" class="form-control"
               value="<?= htmlspecialchars($fromDate) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" name="to_date" class="form-control"
               value="<?= htmlspecialchars($toDate) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control"
               placeholder="Search username"
               value="<?= htmlspecialchars($username) ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>

    <!-- Logs Table -->
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr>
                <th>Time</th>
                <th>Username</th>
                <th>Role</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($logs->num_rows === 0): ?>
                <tr>
                  <td colspan="4" class="text-center py-3">
                    No logs found.
                  </td>
                </tr>
              <?php else: ?>
                <?php while ($row = $logs->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['login_time']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
                    <td><?= htmlspecialchars($row['ip_address']) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
              <a
                class="page-link"
                href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
              ><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</body>
</html>
