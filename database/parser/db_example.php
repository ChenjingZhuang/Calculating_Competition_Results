<?php

$host = '127.0.0.1';
$database = 'cycling_cup_system';
$username = 'YOUR_USERNAME';
$password = 'OUR_PASSWORD';

$dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}