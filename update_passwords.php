<?php
// update_passwords.php — run once, then delete
require_once 'config.php';

$db = getDbConnection();
$users = ['ravin', 'kiran', 'sudip'];
foreach ($users as $u) {
    // generate a brand-new full-length bcrypt hash for "123"
    $newHash = password_hash('123', PASSWORD_DEFAULT);

    // update that user
    $stmt = $db->prepare("UPDATE staff SET password_hash = ? WHERE username = ?");
    $stmt->bind_param('ss', $newHash, $u);

    if ($stmt->execute()) {
        echo "✅ Updated {$u}\n";
    } else {
        echo "❌ Error for {$u}: " . $stmt->error . "\n";
    }
    $stmt->close();
}

$db->close();
