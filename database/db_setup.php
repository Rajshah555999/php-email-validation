<?php
// Database configuration - Using PostgreSQL
$host = getenv('PGHOST');
$dbname = getenv('PGDATABASE');
$username = getenv('PGUSER');
$password = getenv('PGPASSWORD');
$port = getenv('PGPORT');

try {
    // Connect to PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$username;password=$password";
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table for email validation records
    // PostgreSQL uses SERIAL type for auto-incrementing IDs
    $sql = "CREATE TABLE IF NOT EXISTS email_validations (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        is_valid BOOLEAN,
        validation_type VARCHAR(50),
        error_message TEXT,
        mx_records TEXT,
        validation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INTEGER NULL
    )";
    
    $pdo->exec($sql);
    echo "Email validations table created successfully or already exists.<br>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )";
    
    $pdo->exec($sql);
    echo "Users table created successfully or already exists.<br>";
    
    echo "Database setup completed successfully.<br>";
    echo "<a href='../index.php'>Go to Email Validator</a>";
    
} catch(PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
