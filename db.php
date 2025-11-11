<?php
// db.php
$DB_HOST = 'localhost';
$DB_NAME = 'cook_corner'; // change if yours is different
$DB_USER = 'root';
$DB_PASS = '';            // your MySQL password if any

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
