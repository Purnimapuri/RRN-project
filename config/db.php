<?php
/**
 * Database Connection Configuration
 * Rent and Ride Nepal (RRN)
 *
 * Uses PDO with prepared statements throughout the project
 * to prevent SQL injection.
 */

// ---- Update these values to match your local / server environment ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'rrn_db');
define('DB_USER', 'root');
define('DB_PASS', '');
// ------------------------------------------------------------------------

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never leak DB credentials/details to the browser in production
    die("Database connection failed. Please try again later.");
}
