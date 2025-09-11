<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $host = '127.0.0.1';
  $dbname = 'menu';
  $user = 'root';
  $pass = ''; // padrão XAMPP
  $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
