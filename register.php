<?php
// register.php — run once to create staff accounts, then delete or secure
session_start();
require_once 'config.php'; // defines getDbConnection()

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($username === '' || $password === '' || $role === '') {
        $errors[] = 'Please fill in all fields.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db = getDbConnection();
        $stmt = $db->prepare(
            "INSERT INTO staff (username, password_hash, role, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        if (!$stmt) {
            $errors[] = 'DB prepare error: ' . $db->error;
        } else {
            $stmt->bind_param('sss', $username, $hash, $role);
            if ($stmt->execute()) {
                $success = "User '$username' created as $role.";
            } else {
                $errors[] = 'Execute error: ' . $stmt->error;
            }
            $stmt->close();
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register New Staff</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
  <div class="container" style="max-width:400px">
    <h2 class="mb-4 text-center">Create Staff Account</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm bg-white">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <option value="admin"<?php echo (($_POST['role'] ?? '')==='admin')?' selected':''; ?>>Admin</option>
          <option value="police_officer"<?php echo (($_POST['role'] ?? '')==='police_officer')?' selected':''; ?>>Police Officer</option>
          <option value="analytics"<?php echo (($_POST['role'] ?? '')==='analytics')?' selected':''; ?>>Analytics</option>
        </select>
      </div>
      <button class="btn btn-primary w-100">Create Account</button>
    </form>
  </div>
</body>
</html>
