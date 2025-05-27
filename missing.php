<?php
session_start();
require_once 'config.php';

// HTML-escape helper
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = '';

// Handle new missing-person report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'report_missing') {
    $name                = trim($_POST['name'] ?? '');
    $age                 = intval($_POST['age'] ?? 0);
    $gender              = $_POST['gender'] ?? '';
    $last_seen_datetime  = $_POST['last_seen_datetime'] ?? '';
    $last_seen_location  = trim($_POST['last_seen_location'] ?? '');
    $description         = trim($_POST['description'] ?? '');
    $reward_amount       = floatval($_POST['reward_amount'] ?? 0);
    $photo_path          = '';

    // Validation
    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if (!in_array($gender, ['Male','Female','Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }
    if ($last_seen_datetime === '') {
        $errors[] = 'Last seen date & time is required.';
    }
    if ($last_seen_location === '') {
        $errors[] = 'Last seen location is required.';
    }

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $orig = basename($_FILES['photo']['name']);
        $new  = time() . '_' . mt_rand(1000,9999) . '_' . $orig;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $new)) {
            $photo_path = 'uploads/' . $new;
        } else {
            $errors[] = 'Failed to upload photo.';
        }
    }

    // Insert into database
    if (empty($errors)) {
        $conn = getDbConnection();
        $stmt = $conn->prepare(
            "INSERT INTO missing_persons
             (name, age, gender, last_seen_datetime, last_seen_location,
              description, photo_path, reward_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param(
                'sisssssd',
                $name, $age, $gender,
                $last_seen_datetime, $last_seen_location,
                $description, $photo_path, $reward_amount
            );
            if ($stmt->execute()) {
                $success = 'Missing person reported successfully.';
            } else {
                $errors[] = 'Submission failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        $conn->close();
    }
}

// Ensure missing_persons table exists (run only once, auto-create if missing)
$conn = getDbConnection();
$tableCheck = $conn->query("SHOW TABLES LIKE 'missing_persons'");
if ($tableCheck && $tableCheck->num_rows === 0) {
    $createSQL = <<<SQL
CREATE TABLE `missing_persons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `age` INT DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `last_seen_datetime` DATETIME DEFAULT NULL,
  `last_seen_location` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `reward_amount` DECIMAL(10,2) DEFAULT 0.00,
  `reported_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    if (!$conn->query($createSQL)) {
        die("Failed to create missing_persons table: " . $conn->error);
    }
}

// Fetch active cases
$result = $conn->query("SELECT * FROM missing_persons ORDER BY reported_at DESC");
$cases  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();

$totalCases = count($cases);
$perPage    = 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Missing Persons & Rewards | Police NSW CMS</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    .case-thumb { height: 180px; object-fit: cover; cursor: pointer; }
    #searchInput { max-width: 300px; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="fas fa-shield-alt"></i> Police NSW CMS
    </a>
  </div>
</nav>

<div class="container my-5">
  <h1 class="mb-4 text-center">Missing Persons & Rewards</h1>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="mpTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="list-tab" data-bs-toggle="tab"
              data-bs-target="#list" type="button" role="tab">
        <i class="fas fa-images"></i> Active Cases
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="report-tab" data-bs-toggle="tab"
              data-bs-target="#report" type="button" role="tab">
        <i class="fas fa-plus-circle"></i> Report Missing
      </button>
    </li>
  </ul>

  <div class="tab-content" id="mpTabsContent">
    <!-- Active Cases Gallery -->
    <div class="tab-pane fade show active" id="list" role="tabpanel">
      <?php if ($totalCases === 0): ?>
        <p class="text-center">No active cases at the moment.</p>
      <?php else: ?>
        <div class="row g-4" id="caseContainer">
          <?php foreach ($cases as $c): ?>
            <div class="col-md-4 case-card"
                 data-name="<?= strtolower(h($c['name'])) ?>"
                 data-location="<?= strtolower(h($c['last_seen_location'])) ?>"
                 data-reported="<?= strtotime($c['reported_at']) ?>"
                 data-reward="<?= $c['reward_amount'] ?>">
              <div class="card h-100 shadow-sm">
                <?php if ($c['photo_path']): ?>
                  <img src="<?= h($c['photo_path']) ?>" class="card-img-top case-thumb" alt="Photo of <?= h($c['name']) ?>">
                <?php else: ?>
                  <img src="https://via.placeholder.com/350x180?text=No+Photo" class="card-img-top case-thumb" alt="No photo">
                <?php endif; ?>
                <div class="card-body">
                  <h5 class="card-title"><?= h($c['name']) ?> (<?= h($c['gender']) ?>, <?= h($c['age']) ?>)</h5>
                  <p class="card-text"><b>Last Seen:</b> <?= h($c['last_seen_location']) ?> <br>
                    <b>Date & Time:</b> <?= h($c['last_seen_datetime']) ?></p>
                  <p class="card-text"><?= h($c['description']) ?></p>
                  <?php if ($c['reward_amount'] > 0): ?>
                    <span class="badge bg-warning text-dark">Reward: $<?= number_format($c['reward_amount'], 2) ?></span>
                  <?php endif; ?>
                </div>
                <div class="card-footer text-muted small">
                  Reported at: <?= h($c['reported_at']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Report Missing Form -->
    <div class="tab-pane fade" id="report" role="tabpanel">
      <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul>
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm bg-white">
        <input type="hidden" name="form_type" value="report_missing">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Age</label>
            <input type="number" name="age" class="form-control" min="0" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select" required>
              <option value="">Select</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Reward Amount (AUD)</label>
            <input type="number" name="reward_amount" class="form-control" min="0" step="0.01">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Last Seen Date & Time</label>
          <input type="datetime-local" name="last_seen_datetime" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Last Seen Location</label>
          <input type="text" name="last_seen_location" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Photo (optional)</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
        </div>
        <button class="btn btn-primary">Submit Report</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
