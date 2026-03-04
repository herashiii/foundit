<?php
// includes/db.php
declare(strict_types=1);

/**
 * XAMPP defaults:
 * - host: 127.0.0.1
 * - user: root
 * - pass: '' (empty)
 * - db: foundit_db
 */
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host = '127.0.0.1';
  $dbname = 'foundit_db';
  $user = 'root';
  $pass = ''; // XAMPP default is empty password
  $charset = 'utf8mb4';

  $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  try {
    $pdo = new PDO($dsn, $user, $pass, $options);
  } catch (PDOException $e) {
    // In dev, show a readable error. In production, log this instead.
    http_response_code(500);
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
  }

  return $pdo;
}
