<?php
require_once __DIR__ . '/../config/db.php';

class Ingredient {
  public static function listByProduct(int $productId): array {
    $st = db()->prepare('SELECT * FROM ingredients WHERE product_id = ? ORDER BY id');
    $st->execute([$productId]);
    return $st->fetchAll();
  }

  public static function replaceForProduct(int $productId, array $names): void {
    $db = db();
    $db->prepare('DELETE FROM ingredients WHERE product_id = ?')->execute([$productId]);
    if (empty($names)) return;
    $st = $db->prepare('INSERT INTO ingredients (product_id, name) VALUES (?, ?)');
    foreach ($names as $name) {
      $st->execute([$productId, $name]);
    }
  }
}
?>
