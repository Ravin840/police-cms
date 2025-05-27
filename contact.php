<?php
// contact.php
session_start();
require_once 'config.php';

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($message === '') {
        $errors[] = 'Please enter a message.';
    }

    if (empty($errors)) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO contact_messages
              (name, email, message)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('sss', $name, $email, $message);
        if ($stmt->execute()) {
            $sent = true;
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
        $db->close();
    }
}

// If sent, show thank you:
if ($sent): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Thank You</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="d-flex flex-column justify-content-center align-items-center vh-100 bg-light">
  <div class="text-center p-5 bg-white shadow rounded">
    <h1>Thank You!</h1>
    <p class="lead">
      Thank you for connecting with the Police Department. We’ve received your message and will get back to you shortly.
    </p>
    <a href="index.php" class="btn btn-primary">Back to Home</a>
  </div>
</body>
</html>
<?php
    exit;
endif;

// Otherwise, redisplay the form with errors:
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us — Police NSW CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>
<body class="bg-light py-5">
  <div class="container">
    <h2 class="text-center mb-4">Contact Us</h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="contact.php" method="POST" class="w-75 mx-auto">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input
          type="text"
          name="name"
          class="form-control"
          value="<?= h($name ?? '') ?>"
          required
        >
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input
          type="email"
          name="email"
          class="form-control"
          value="<?= h($email ?? '') ?>"
          required
        >
      </div>
      <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea
          name="message"
          class="form-control"
          rows="4"
          required
        ><?= h($message ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary w-100">
        Send Message
      </button>
    </form>
  </div>
</body>
</html>
