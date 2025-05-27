<?php
session_start();
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDbConnection();

        // 1) Fetch user record
        $stmt = $conn->prepare("SELECT id, password_hash, role FROM staff WHERE username = ?");
        if (!$stmt) {
            die("DB error (prepare): " . $conn->error);
        }
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            $error = 'Username not found.';
        } else {
            $row = $result->fetch_assoc();
            // 2) Verify password
            if (password_verify($password, $row['password_hash'])) {
                // 3) Set session
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $row['role'];

                // 4) Log login
                $log = $conn->prepare(
                    "INSERT INTO login_logs (user_id, login_time, ip_address) VALUES (?, NOW(), ?)"
                );
                $ip = $_SERVER['REMOTE_ADDR'];
                $log->bind_param('is', $row['id'], $ip);
                $log->execute();
                $log->close();

                // 5) Redirect based on role
                switch ($row['role']) {
                    case 'admin':
                        header('Location: admin_dashboard.php');
                        exit;
                    case 'police_officer':
                        header('Location: officer_dashboard.php');
                        exit;
                    case 'forensic_officer': // Match your DB value
                        header('Location: forensic_portal.php');
                        exit;
                    default:
                        header('Location: index.php');
                        exit;
                }
            } else {
                $error = 'Incorrect password.';
            }
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center"
      style="height: 100vh;
             margin: 0;
             background: url('assets/background.jpg') no-repeat center center fixed;
             background-size: cover;">
  <form method="post"
        class="p-4 rounded shadow-sm"
        style="width: 320px;
               background: rgba(255, 255, 255, 0.85);">
    <!-- Logo -->
    <div class="text-center mb-3">
      <img src="po.jpg"  style="max-width:150px;">
    </div>
    <h4 class="mb-3 text-center">Staff Login</h4>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="mb-2">
      <input
        type="text"
        name="username"
        class="form-control"
        placeholder="Username"
        required
        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
      >
    </div>
    <div class="mb-3">
      <input
        type="password"
        name="password"
        class="form-control"
        placeholder="Password"
        required
      >
    </div>
    <button class="btn btn-primary w-100">Log In</button>
    <!-- Back to Home Button -->
    <div class="text-center mt-3">
      <a href="index.php" class="btn btn-link text-decoration-none">Back to Home</a>
    </div>
  </form>
</body>
</html>