<?php
session_start();
require_once 'config.php';

// Only allow admin & analytics
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','analytics'], true)) {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();

// 1. Total reports
$res = $db->query("SELECT COUNT(*) AS total FROM cases");
$metrics['total'] = $res->fetch_assoc()['total'] ?? 0;

// 2. Counts by status
$statusCounts = [];
$res = $db->query("SELECT status, COUNT(*) AS cnt FROM cases GROUP BY status");
while ($r = $res->fetch_assoc()) {
    $statusCounts[$r['status']] = $r['cnt'];
}

// 3. Counts by type
$typeCounts = [];
$res = $db->query("SELECT report_type, COUNT(*) AS cnt FROM cases GROUP BY report_type");
while ($r = $res->fetch_assoc()) {
    $typeCounts[$r['report_type']] = $r['cnt'];
}

// 4. Recent cases (fixed column names)
$recentRes = $db->query(
    "SELECT id AS case_id, title, status, date_reported
       FROM cases
      ORDER BY date_reported DESC
      LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reports & Analytics</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light p-4">
  <div class="container-fluid">
    <h2 class="mb-4">Reports & Analytics</h2>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card text-white bg-primary p-3">
          <h5>Total Reports</h5>
          <h3><?php echo htmlspecialchars($metrics['total']); ?></h3>
        </div>
      </div>
      <?php foreach ($statusCounts as $status => $count): ?>
        <div class="col-md-3">
          <div class="card p-3">
            <h6><?php echo ucfirst($status); ?></h6>
            <h4><?php echo htmlspecialchars($count); ?></h4>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card p-3 mb-3">
          <h5>Cases by Status</h5>
          <canvas id="statusChart"></canvas>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3 mb-3">
          <h5>Cases by Type</h5>
          <canvas id="typeChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Recent Reports Table -->
    <div class="card">
      <div class="card-header">Recent Reports</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th><th>Title</th><th>Status</th><th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $recentRes->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['case_id']); ?></td>
                  <td><?php echo htmlspecialchars($row['title']); ?></td>
                  <td><?php echo htmlspecialchars($row['status']); ?></td>
                  <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Cases by Status Pie Chart
    new Chart(document.getElementById('statusChart'), {
      type: 'pie',
      data: {
        labels: <?php echo json_encode(array_keys($statusCounts)); ?>,
        datasets: [{
          data: <?php echo json_encode(array_values($statusCounts)); ?>,
          backgroundColor: ['#007bff','#28a745','#dc3545','#ffc107']
        }]
      }
    });

    // Cases by Type Bar Chart
    new Chart(document.getElementById('typeChart'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_keys($typeCounts)); ?>,
        datasets: [{
          data: <?php echo json_encode(array_values($typeCounts)); ?>,
          backgroundColor: ['#17a2b8','#6f42c1','#e83e8c','#fd7e14']
        }]
      },
      options: { scales: { y: { beginAtZero: true } } }
    });
  </script>
</body>
</html>
