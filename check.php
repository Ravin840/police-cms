<?php
require_once 'config.php';

// Connect to the DB
$db = getDbConnection();

// Header for clarity
echo "<h2>Verifying password '123' for each user</h2><pre>";

// Query all users
$res = $db->query("SELECT username, password_hash FROM staff");
if (!$res) {
    die("DB error: " . $db->error);
}

// Test each one
while ($row = $res->fetch_assoc()) {
    $ok = password_verify('123', $row['password_hash']) ? 'OK' : 'FAIL';
    echo "{$row['username']}: {$ok}\n";
}

echo "</pre>";
