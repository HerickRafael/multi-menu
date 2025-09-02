<?php /* Tailwind via CDN */ ?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? $company['name'] ?? 'Cardápio') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
  <div class="max-w-5xl mx-auto p-4">
    <?= $content ?? '' ?>
  </div>
</body>
</html>
