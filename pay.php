<?php
// pay.php
session_start();
require_once 'config.php';

// HTML-escape helper
define('CHARSET', 'UTF-8');
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, CHARSET);
}

// --- Ensure fine_payments table exists ---
$dbInit = getDbConnection();
$tableCheck = $dbInit->query("SHOW TABLES LIKE 'fine_payments'");
if ($tableCheck && $tableCheck->num_rows === 0) {
    $createSQL = <<<SQL
CREATE TABLE `fine_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `license_no` VARCHAR(50) NOT NULL,
  `fine_id` INT NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `paid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
    if (!$dbInit->query($createSQL)) {
        die("Failed to create fine_payments table: " . $dbInit->error);
    }
}
$dbInit->close();

// 1) Grab & validate GET parameters
$method  = $_GET['method']       ?? '';
$license = trim($_GET['license'] ?? '');
$fineId  = intval($_GET['fine_id'] ?? 0);
$amount  = floatval($_GET['amount'] ?? 0);

if (
    !in_array($method, ['card','paypal','apple'], true)
    || $license === ''
    || $fineId <= 0
    || $amount <= 0
) {
    header('Location: payment.php');
    exit;
}

// 2) Prepare for processing
$errors  = [];
$success = '';

// ─── CREDIT/DEBIT CARD FLOW ───
if ($method === 'card') {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['form_type'] ?? '') === 'card_payment'
    ) {
        // Collect & sanitize
        $cn  = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $nm  = trim($_POST['card_name'] ?? '');
        $em  = intval($_POST['exp_month'] ?? 0);
        $ey  = intval($_POST['exp_year']  ?? 0);
        $cvv = trim($_POST['cvv'] ?? '');

        // Validate
        if (strlen($cn) !== 16)            $errors[] = 'Card number must be 16 digits.';
        if ($nm === '')                   $errors[] = 'Name on card is required.';
        if ($em < 1 || $em > 12 || $ey < intval(date('Y'))) 
                                          $errors[] = 'Invalid expiry date.';
        if (!preg_match('/^\d{3,4}$/', $cvv))
                                          $errors[] = 'CVV must be 3 or 4 digits.';

        // If valid, record stub payment
        if (empty($errors)) {
            $db = getDbConnection();
            $st = $db->prepare("INSERT INTO fine_payments (license_no, fine_id, amount_paid) VALUES (?, ?, ?)");
            if ($st) {
                $st->bind_param('sid', $license, $fineId, $amount);
                if ($st->execute()) {
                    $success = 'Payment successful! Transaction ID: ' . $db->insert_id;
                } else {
                    $errors[] = 'Database error: ' . $db->error;
                }
                $st->close();
            } else {
                $errors[] = 'Database prepare error: ' . $db->error;
            }
            $db->close();
        }
    }

    // Render card form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Credit Card Payment</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    </head>
    <body class="container py-5">
      <a href="payment.php" class="btn btn-outline-secondary mb-4">← Back</a>
      <h2>Credit/Debit Card Payment</h2>
      <p>
        <strong>License:</strong> <?= h($license) ?>  
        <strong>Fine ID:</strong> <?= h($fineId) ?>  
        <strong>Amount:</strong> AUD <?= number_format($amount,2) ?>
      </p>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
      <?php elseif ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <form method="POST" class="card p-4 shadow-sm bg-white">
        <input type="hidden" name="form_type" value="card_payment">
        <input type="hidden" name="license" value="<?= h($license) ?>">
        <input type="hidden" name="fine_id" value="<?= h($fineId) ?>">
        <input type="hidden" name="amount" value="<?= h($amount) ?>">

        <div class="mb-3">
          <label class="form-label">Card Number</label>
          <input type="text" name="card_number" class="form-control" placeholder="4111 1111 1111 1111" value="<?= h($_POST['card_number']??'') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Name on Card</label>
          <input type="text" name="card_name" class="form-control" value="<?= h($_POST['card_name']??'') ?>" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Expiry Month</label>
            <input type="number" name="exp_month" class="form-control" min="1" max="12" value="<?= h($_POST['exp_month']??'') ?>" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Expiry Year</label>
            <input type="number" name="exp_year" class="form-control" min="<?= date('Y') ?>" max="<?= date('Y')+10 ?>" value="<?= h($_POST['exp_year']??'') ?>" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">CVV</label>
          <input type="password" name="cvv" class="form-control" maxlength="4" required>
        </div>

        <button type="submit" class="btn btn-success w-100"><i class="fas fa-lock"></i> Pay <?= number_format($amount,2) ?> AUD</button>
      </form>
    </body>
    </html>
    <?php
    exit;
}

// ─── PAYPAL SANDBOX FLOW ───
if ($method === 'paypal') {
    $business  = 'sb-XXXXX@business.example.com'; // Replace with your real PayPal sandbox account email
    $returnURL = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment.php?tab=pay';
    $cancelURL = $returnURL;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>PayPal Checkout</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    </head>
    <body class="container py-5">
      <a href="payment.php" class="btn btn-outline-secondary mb-4">← Back</a>
      <h2><i class="fab fa-paypal"></i> PayPal Checkout</h2>
      <p>Amount: AUD <?= number_format($amount,2) ?></p>
      <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="business" value="<?= h($business) ?>">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="item_name" value="Fine #<?= h($fineId) ?>">
        <input type="hidden" name="amount" value="<?= h($amount) ?>">
        <input type="hidden" name="currency_code" value="AUD">
        <input type="hidden" name="return" value="<?= h($returnURL) ?>">
        <input type="hidden" name="cancel_return" value="<?= h($cancelURL) ?>">
        <button type="submit" class="btn btn-info w-100"><i class="fab fa-paypal"></i> Pay with PayPal</button>
      </form>
    </body>
    </html>
    <?php
    exit;
}

// ─── APPLE PAY STUB ───
if ($method === 'apple') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Apple Pay</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    </head>
    <body class="text-center py-5">
      <a href="payment.php" class="btn btn-outline-secondary mb-4">← Back</a>
      <h2><i class="fab fa-apple"></i> Apple Pay</h2>
      <p>This demo doesn’t implement Apple Pay—please use Card or PayPal.</p>
      <p><a href="payment.php?tab=pay" class="btn btn-primary mt-3">Return to Payment Options</a></p>
    </body>
    </html>
    <?php
    exit;
}

// fallback: if code execution reaches here, redirect.
header('Location: payment.php');
exit;
?>
