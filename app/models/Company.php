<?php
require_once __DIR__ . '/../config/db.php';
class Company {
  public static function findBySlug(string $slug): ?array {
    $st = db()->prepare("SELECT * FROM companies WHERE slug = ? LIMIT 1");
    $st->execute([$slug]);
    return $st->fetch() ?: null;
  }
}
