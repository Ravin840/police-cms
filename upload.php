<?php
// upload_evidence.php
session_start();
require 'config.php';

// 1) Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police_officer') {
    header('Location: login.php');
    exit;
}

$mysqli    = getDbConnection();
$officerId = (int)($_SESSION['user_id'] ?? 1);
$username  = htmlspecialchars($_SESSION['username'] ?? 'Officer');

// 2) Ensure this officer exists in officers table (CORRECT COLUMN: full_name)
$upsert = $mysqli->prepare(
  "INSERT INTO officers (officer_id, full_name)
     VALUES (?, ?)
   ON DUPLICATE KEY UPDATE
     full_name = VALUES(full_name)"
);
$upsert->bind_param('is', $officerId, $username);
$upsert->execute();
$upsert->close();

// 3) Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file        = $_FILES['evidence_file'] ?? null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a file to upload.';
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName  = uniqid('evi_') . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            // 4) Now safe to insert into evidence
            $stmt = $mysqli->prepare(
              "INSERT INTO evidence
                 (officer_id, title, description, file_path)
               VALUES (?, ?, ?, ?)"
            );
            $relativePath = 'uploads/' . $newName;
            $stmt->bind_param(
              'isss',
              $officerId,
              $title,
              $description,
              $relativePath
            );
            if ($stmt->execute()) {
                $success = 'Evidence uploaded successfully.';
            } else {
                $errors[] = 'DB error: ' . $stmt->error;
                unlink($destPath);
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to move uploaded file.';
        }
    }
}

// 5) Fetch recent evidence
$stmt = $mysqli->prepare(
  "SELECT title, description, file_path,
          DATE_FORMAT(uploaded_at, '%d %b %Y %H:%i') AS uploaded_at
     FROM evidence
    WHERE officer_id = ?
    ORDER BY uploaded_at DESC
    LIMIT 5"
);
$stmt->bind_param('i', $officerId);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Evidence</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap 5 & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fa-solid fa-folder-plus me-2"></i>Evidence Upload</a>
      <div class="d-flex align-items-center">
        <span class="me-3"><i class="fa-regular fa-user"></i> <?= $username ?></span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>
      </div>
    </div>
  </nav>

  <div class="container">
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach ?>
      </ul></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif ?>

    <div class="card mb-5 shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fa-solid fa-upload me-2 text-primary"></i>New Evidence</h5>
      </div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Evidence File</label>
            <input type="file" name="evidence_file" class="form-control" accept=".jpg,.png,.pdf,.docx" required>
            <div class="form-text">Allowed: JPG, PNG, PDF, DOCX.</div>
          </div>
          <button class="btn btn-primary">
            <i class="fa-solid fa-check me-1"></i>Upload Evidence
          </button>
        </form>
      </div>
    </div>

    <h5>Recent Uploads</h5>
    <ul class="list-group mb-5">
      <?php if ($recent): foreach ($recent as $r): ?>
        <li class="list-group-item">
          <strong><?= htmlspecialchars($r['title']) ?></strong>
          <small class="text-muted">(<?= $r['uploaded_at'] ?>)</small>
          <p class="mb-1"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
          <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">View File</a>
        </li>
      <?php endforeach; else: ?>
        <li class="list-group-item text-muted">No evidence uploaded yet.</li>
      <?php endif ?>
    </ul>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
