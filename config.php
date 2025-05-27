<?php
// config.php

// Database credentials
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'crime_db');

/**
 * Returns a connected mysqli instance pointed at the crime_db database.
 *
 * @return mysqli
 */
function getDbConnection() {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // Check for connection errors
    if ($conn->connect_error) {
        // Fatal: stop execution and show error
        die('Database connection failed: ' . $conn->connect_error);
    }

    // Explicitly select the correct database
    $conn->select_db(DB_NAME);

    return $conn;
}
