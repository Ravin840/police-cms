<?php
// contact.php
session_start();
require_once 'config.php';

/** HTML‐escape helper */
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$errors = [];

// Only accept POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');

    // Basic validation
    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($message === '') {
        $errors[] = 'Please enter a message.';
    }

    if (empty($errors)) {
        // Insert into DB
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO contact_messages
              (name, email, message)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('sss', $name, $email, $message);
        $stmt->execute();
        $stmt->close();
        $db->close();

        // Show thank you page
        ?>
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
            <h1 class="mb-4">Thank You!</h1>
            <p class="lead">Thank you for connecting with the Police Department. We’ve received your message and will get back to you shortly.</p>
            <a href="index.php" class="btn btn-primary mt-3">Back to Home</a>
          </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// If we get here, either it wasn’t a POST or there were validation errors.
// Redirect back to the contact section with errors in the query string.
$query = http_build_query([
    'errors' => $errors
]);
header("Location: index.php#contact?$query");
exit;
