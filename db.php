<?php
declare(strict_types=1);

/**
 * Database Connection Configuration
 * Sunrise Community Hospital Management System
 */

$host = 'localhost';
$db   = 'hospital_management'; 
$user = 'root';             
$pass = '';                
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log the error message internally and present a user-friendly message
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}