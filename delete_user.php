<?php
session_start();
require_once 'config.php';

// Only admins can delete
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Must be POST with an id
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: manage_users.php');
    exit;
}

$id = intval($_POST['id']);

// Prevent deleting yourself
if ($id === intval($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You cannot delete your own account.';
    header('Location: manage_users.php');
    exit;
}

$db = getDbConnection();

// 1) Remove any login logs for that user
$delLogs = $db->prepare("DELETE FROM login_logs WHERE user_id = ?");
$delLogs->bind_param('i', $id);
$delLogs->execute();
$delLogs->close();

// 2) Delete the user
$delUser = $db->prepare("DELETE FROM staff WHERE id = ?");
$delUser->bind_param('i', $id);
if ($delUser->execute()) {
    $_SESSION['success'] = 'User deleted successfully.';
} else {
    $_SESSION['error'] = 'Delete error: ' . $delUser->error;
}
$delUser->close();
$db->close();

// Redirect back to the management page
header('Location: manage_users.php');
exit;
?>
