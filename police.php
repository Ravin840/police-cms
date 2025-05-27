<?php
// staff_index.php — Police Officer Portal Home

session_start();
require_once 'config.php';

// 1) Only allow logged-in officers here
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'police_officer') {
    header('Location: login.php');
    exit;
}

// 2) (Optional) Fetch some quick stats for the dashboard
$conn = getDbConnection();
$stmt = $conn->prepare("
    SELECT 
      COUNT(*) AS total_cases,
      SUM(status = 'open') AS open_cases
    FROM cases
    WHERE assigned_officer = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($totalCases, $openCases);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Officer Portal</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body { background: #f5f7fa; }
    .card-hover:hover {
      transform: scale(1.02);
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Officer Portal</a>
      <button class="navbar-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#navItems">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navItems">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="staff_index.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="cases.php">My Cases</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="activity.php">Activity Log</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <main class="container py-4">
    <div class="d-flex align-items-center mb-4">
      <h1 class="me-auto">
        Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
      </h1>
      <span class="badge bg-primary">Officer</span>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="card card-hover p-3 h-100">
          <div class="d-flex align-items-center">
            <i class="fas fa-folder-open fa-2x me-3 text-primary"></i>
            <div>
              <h5 class="card-title mb-1">Assigned Cases</h5>
              <p class="card-text">
                <?= $openCases ?>/<?= $totalCases ?> open
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-hover p-3 h-100">
          <div class="d-flex align-items-center">
            <i class="fas fa-list-alt fa-2x me-3 text-success"></i>
            <div>
              <h5 class="card-title mb-1">Activity Log</h5>
              <p class="card-text">View your recent actions</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <a href="cases.php" class="text-decoration-none text-dark">
          <div class="card card-hover p-3 h-100">
            <div class="d-flex align-items-center">
              <i class="fas fa-edit fa-2x me-3 text-warning"></i>
              <div>
                <h5 class="card-title mb-1">Update Case</h5>
                <p class="card-text">Add notes or change status</p>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-4">
        <a href="activity.php" class="text-decoration-none text-dark">
          <div class="card card-hover p-3 h-100">
            <div class="d-flex align-items-center">
              <i class="fas fa-clock fa-2x me-3 text-info"></i>
              <div>
                <h5 class="card-title mb-1">Recent Activity</h5>
                <p class="card-text">Audit your session history</p>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div class="col-md-4">
        <a href="profile.php" class="text-decoration-none text-dark">
          <div class="card card-hover p-3 h-100">
            <div class="d-flex align-items-center">
              <i class="fas fa-user-circle fa-2x me-3 text-secondary"></i>
              <div>
                <h5 class="card-title mb-1">My Profile</h5>
                <p class="card-text">Edit your details & password</p>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
