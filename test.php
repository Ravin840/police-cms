<?php
echo 'PHP version: ' . PHP_VERSION . "<br>";

$test_hash = password_hash('123', PASSWORD_DEFAULT);
echo "Freshly generated hash: $test_hash<br>";

$password = '123';
echo "Password: $password<br>";

echo "Length of freshly generated hash: " . strlen($test_hash) . "<br>";

if (password_verify($password, $test_hash)) {
    echo "SUCCESS: Freshly generated password is valid!<br>";
} else {
    echo "FAIL: Freshly generated password is invalid!<br>";
}

// Now try the "fixed" hash again:
$hash = '$2y$10$0UCjZlGpp5yPWGOsYa3c0e5qJZd03qH8YVZ95V83X/4brLDjBcWGe';

if (password_verify($password, $hash)) {
    echo "SUCCESS: Provided hash is valid!<br>";
} else {
    echo "FAIL: Provided hash is invalid!<br>";
}
?>
