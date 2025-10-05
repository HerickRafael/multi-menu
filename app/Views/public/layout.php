<?php /* Tailwind via CDN */ ?>
<?php
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slug = trim($slug, '/');
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slug = trim($slug, '/');

if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
$homeUrl = function_exists('base_url') ? base_url($slug !== '' ? $slug : '') : '#';
$cartUrl = function_exists('base_url') ? base_url(($slug !== '' ? $slug . '/' : '') . 'cart') : '#';
$profileUrl = function_exists('base_url') ? base_url(($slug !== '' ? $slug . '/' : '') . 'profile') : '#';

$cartItemCount = 0;
try {
    if (class_exists('CartStorage')) {
        $cartItems = CartStorage::instance()->getCart();

        if (is_array($cartItems)) {
            foreach ($cartItems as $entry) {
                $cartItemCount += max(0, (int)($entry['qty'] ?? 0));
            }
        }
    } elseif (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $entry) {
            $cartItemCount += max(0, (int)($entry['qty'] ?? 0));
        }
    }
} catch (Throwable $layoutCartEx) {
    $cartItemCount = 0;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Cardápio') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
</head>
<body class="bg-gray-50 text-gray-900">
  <div class="max-w-5xl mx-auto<?= !empty($showFooterMenu) ? ' p-4' : '' ?>">
    <?= $content ?? '' ?>
  </div>
  <?php if (!empty($showFooterMenu)): ?>
  <nav class="fixed bottom-0 left-0 right-0 bg-white border-t">
    <div class="max-w-5xl mx-auto flex justify-around py-2">
      <a href="<?= e($homeUrl) ?>" class="flex flex-col items-center text-black">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
        <span class="text-xs">Home</span>
      </a>
      <a href="<?= e($cartUrl) ?>" class="relative flex flex-col items-center text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
        <?php if ($cartItemCount > 0): ?>
          <span class="absolute -top-1.5 right-2 inline-flex min-w-[18px] min-h-[18px] px-1.5 items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-semibold leading-none">
            <?= e($cartItemCount) ?>
          </span>
        <?php endif; ?>
        <span class="text-xs">Sacola</span>
      </a>
      <a href="<?= e($profileUrl) ?>" class="flex flex-col items-center text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
        <span class="text-xs">Perfil</span>
      </a>
    </div>
  </nav>
  <?php endif; ?>
  <script>
    // expose customer state for client-side handlers
    window.__IS_CUSTOMER = <?= !empty($_SESSION['customer']) ? 'true' : 'false' ?>;
  </script>
  <script src="<?= base_url('assets/js/ui.js') ?>"></script>
</body>
</html>
