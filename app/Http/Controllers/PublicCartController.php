<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;

final class PublicCartController extends Controller
{
    public function index(array $params): void
    {
        $slug = $params['slug'] ?? '';

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $cart = $_SESSION['cart'][$slug] ?? [];
        $this->view('public/cart', [
            'slug' => $slug,
            'items' => $cart,
        ]);
    }

    public function add(array $params): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $slug = $params['slug'] ?? '';
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        $_SESSION['cart'][$slug][$productId] = ($_SESSION['cart'][$slug][$productId] ?? 0) + $quantity;

        $this->json([
            'ok' => true,
            'product_id' => $productId,
            'quantity' => $_SESSION['cart'][$slug][$productId],
        ]);
    }
}
