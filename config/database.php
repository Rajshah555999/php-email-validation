<?php
// Database configuration - Using PostgreSQL
$host = getenv('PGHOST');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');
$port = getenv('PGPORT');

// Create database connection
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $pdo = new PDO($dsn);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect to PostgreSQL. " . $e->getMessage());
}
?>
