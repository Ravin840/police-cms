<?php
session_start();
require_once 'config.php'; // defines getDbConnection()

// Only admins can manage users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = getDbConnection();

// Initialize feedback message
if (!isset($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($username === '' || $password === '' || $role === '') {
        $_SESSION['msg'] = 'All fields are required.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO staff (username, password_hash, role, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param('sss', $username, $hash, $role);
        if ($stmt->execute()) {
            $_SESSION['msg'] = 'User added successfully.';
        } else {
            $_SESSION['msg'] = 'Add error: ' . $stmt->error;
        }
        $stmt->close();
    }
    header('Location: manage_users.php');
    exit;
}

// Fetch all staff
$result = $conn->query("
    SELECT id, username, role,
           DATE_FORMAT(created_at,'%Y-%m-%d') AS joined
      FROM staff
  ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | Police NSW CMS</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body { background: #f0f2f5; }
    .table-wrapper {
      background: #fff; padding:20px;
      border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1);
      margin-top:20px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin_dashboard.php">Police CMS</a>
      <button class="navbar-toggler" type="button"
              data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link active" href="manage_users.php">Manage Users</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4">
      <h2>Manage Users</h2>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-user-plus me-1"></i> Add User
      </button>
    </div>

    <?php if ($_SESSION['msg']): ?>
      <div class="alert alert-info mt-3">
        <?= htmlspecialchars($_SESSION['msg']) ?>
      </div>
      <?php $_SESSION['msg'] = ''; ?>
    <?php endif; ?>

    <div class="table-wrapper">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Username</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
              <td><?= $row['joined'] ?></td>
              <td>
                <form method="post" action="delete_user.php" class="d-inline"
                      onsubmit="return confirm('Really delete user <?= htmlspecialchars($row['username']) ?>?');">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="manage_users.php">
          <input type="hidden" name="action" value="add">
          <div class="modal-header">
            <h5 class="modal-title">Add New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" required>
                <option value="admin">Admin</option>
                <option value="police_officer">Officer</option>
                <option value="analytics">Analyst</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
  </script>
</body>
</html>
<?php $conn->close(); ?>
