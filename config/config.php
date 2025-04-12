<?php
$host = 'localhost';        // Change if your MySQL host is different
$db   = 'money_splitter';   // The database name we just created
$db_user = 'root';             // Default username for XAMPP
$pass = '';                 // Default password is empty in XAMPP
$charset = 'utf8mb4';

// Set up DSN and options for PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Try connecting
try {
    $pdo = new PDO($dsn, $db_user, $pass, $options);
    // echo "Database connected successfully!";
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>