<?php
// payment.php
session_start();
require_once 'config.php';

/** HTML-escape helper */
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$tab         = $_GET['tab'] ?? 'pay';
$permErrors  = [];
$permSuccess = '';

// Show payment success (redirected from pay.php)
$paymentSuccess = '';
if (isset($_GET['paid']) && $_GET['paid'] == '1') {
    $txid = isset($_GET['txid']) ? h($_GET['txid']) : '';
    $paymentSuccess = "Payment successful! Transaction ID: $txid";
}

// ─── APPLY PERMIT LOGIC ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'apply_permit') {
    $name       = trim($_POST['applicant_name'] ?? '');
    $type       = trim($_POST['permit_type']   ?? '');
    $details    = trim($_POST['details']       ?? '');
    $supportDoc = '';

    if ($name === '')       $permErrors[] = 'Your name is required.';
    if ($type === '')       $permErrors[] = 'Permit type is required.';

    if (isset($_FILES['support_doc']) && $_FILES['support_doc']['error'] === UPLOAD_ERR_OK) {
        $u = __DIR__ . '/uploads/';
        if (!is_dir($u)) mkdir($u, 0755, true);
        $orig = basename($_FILES['support_doc']['name']);
        $new  = time() . '_' . mt_rand(1000,9999) . '_' . $orig;
        if (move_uploaded_file($_FILES['support_doc']['tmp_name'], $u . $new)) {
            $supportDoc = 'uploads/' . $new;
        } else {
            $permErrors[] = 'Failed to upload document.';
        }
    }

    if (empty($permErrors)) {
        $db = getDbConnection();
        $st = $db->prepare("
          INSERT INTO permits
            (applicant_name, permit_type, details, support_doc)
          VALUES (?,?,?,?)
        ");
        $st->bind_param('ssss', $name, $type, $details, $supportDoc);
        if ($st->execute()) {
            $permSuccess = 'Application submitted! Ref ID: ' . $st->insert_id;
        } else {
            $permErrors[] = 'DB error: ' . $st->error;
        }
        $st->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Police NSW CMS — Public Services</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="#">
      <i class="fas fa-shield-alt"></i> Police NSW CMS
    </a>
  </div>
</nav>

<div class="container">
  <h1 class="text-center mb-4">Public Services</h1>

  <?php if ($paymentSuccess): ?>
    <div class="alert alert-success text-center"><?= $paymentSuccess ?></div>
  <?php endif; ?>

  <ul class="nav nav-pills justify-content-center mb-4">
    <li class="nav-item">
      <a class="nav-link <?= $tab==='pay' ? 'active':'' ?>"
         href="?tab=pay"><i class="fas fa-credit-card"></i> Pay Fine</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='permit' ? 'active':'' ?>"
         href="?tab=permit"><i class="fas fa-file-alt"></i> Apply Permit</a>
    </li>
  </ul>

  <?php if ($tab === 'pay'): ?>
    <!-- Pay Fine Form (submits to pay.php) -->
    <form action="pay.php" method="GET"
          class="card p-4 shadow-sm bg-white">
      <div class="mb-3">
        <label class="form-label">License Number</label>
        <input name="license" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Fine ID</label>
        <input name="fine_id" type="number" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Amount (AUD)</label>
        <input name="amount" type="number" step="0.01"
               class="form-control" required>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" name="method" value="card"
                class="btn btn-primary">Credit Card</button>
        <button type="submit" name="method" value="paypal"
                class="btn btn-info">PayPal</button>
        <button type="submit" name="method" value="apple"
                class="btn btn-dark">Apple Pay</button>
      </div>
    </form>

  <?php else: /* Apply Permit */ ?>
    <?php if ($permSuccess): ?>
      <div class="alert alert-success"><?= h($permSuccess) ?></div>
    <?php elseif ($permErrors): ?>
      <div class="alert alert-danger"><ul>
        <?php foreach ($permErrors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data"
          class="card p-4 shadow-sm bg-white">
      <input type="hidden" name="form_type" value="apply_permit">

      <div class="mb-3">
        <label class="form-label">Your Name</label>
        <input name="applicant_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Permit Type</label>
        <select name="permit_type" class="form-select" required>
          <option value="">Select</option>
          <option>Event</option>
          <option>Weapon</option>
          <option>Other</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Details / Justification</label>
        <textarea name="details" class="form-control"
                  rows="4"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Supporting Document</label>
        <input type="file" name="support_doc"
               class="form-control"
               accept=".pdf,.jpg,.png">
      </div>
      <button class="btn btn-primary w-100">
        <i class="fas fa-paper-plane"></i> Submit Application
      </button>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
