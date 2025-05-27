<?php
session_start();
// Restrict access to admins only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Police CMS Admin Dashboard</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body { min-height: 100vh; background-color: #f8f9fa; }
    .sidebar { height: 100vh; background-color: #343a40; color: #fff; }
    .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 10px; }
    .sidebar a:hover { background-color: #495057; color: #fff; }
    .content { padding: 20px; }
    .card-icon { font-size: 2rem; }
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <nav class="sidebar flex-shrink-0 p-3">
      <h2 class="fs-4 text-white mb-4"><i class="fas fa-shield-alt me-2"></i>Admin Panel</h2>
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
          <a href="manage.php" class="nav-link"><i class="fas fa-users-cog me-2"></i>Manage Users</a>
        </li>
        <li>
          <a href="log.php" class="nav-link"><i class="fas fa-file-alt me-2"></i>View Audit Logs</a>
        </li>
        <li>
          <a href="reports.php" class="nav-link"><i class="fas fa-chart-line me-2"></i>Reports & Analytics</a>
        </li>
        <li>
          <a href="settings.php" class="nav-link"><i class="fas fa-cogs me-2"></i>System Settings</a>
        </li>
        <li>
          <a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i>Log Out</a>
        </li>
      </ul>
    </nav>

    <!-- Main Content -->
    <div class="content flex-grow-1">
      <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3">Welcome, <?php echo $username; ?></h1>
          <small class="text-muted"><?php echo date('l, F j, Y \a\t g:i A'); ?></small>
        </div>

        <!-- Quick Action Cards -->
        <div class="row g-4">
          <div class="col-md-3">
            <div class="card shadow-sm">
              <div class="card-body text-center">
                <div class="card-icon text-primary mb-2"><i class="fas fa-user-plus"></i></div>
                <h5>Add New Officer</h5>
                <a href="register.php" class="stretched-link"></a>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card shadow-sm">
              <div class="card-body text-center">
                <div class="card-icon text-success mb-2"><i class="fas fa-file-signature"></i></div>
                <h5>Review Incident Reports</h5>
                <a href="reports.php" class="stretched-link"></a>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card shadow-sm">
              <div class="card-body text-center">
                <div class="card-icon text-warning mb-2"><i class="fas fa-id-badge"></i></div>
                <h5>Approve Permits & Fines</h5>
                <a href="admin_approvals.php" class="stretched-link"></a>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card shadow-sm">
              <div class="card-body text-center">
                <div class="card-icon text-danger mb-2"><i class="fas fa-bell"></i></div>
                <h5>View Alerts</h5>
                <a href="alerts.php" class="stretched-link"></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
