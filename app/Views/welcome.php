<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $companies */
$companies = $companies ?? [];
$title = 'Multi Menu';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #f5f3ff, #ede9fe 45%, #e0f2fe 100%);
            color: #1f2937;
        }
        .card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 32px 40px;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 20px 45px rgba(79, 70, 229, 0.18);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 1.75rem;
            color: #312e81;
        }
        p {
            margin: 0 0 16px;
            line-height: 1.55;
        }
        ul {
            padding-left: 20px;
            margin: 12px 0 0;
        }
        a {
            color: #4338ca;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover,
        a:focus-visible {
            text-decoration: underline;
        }
        .hint {
            font-size: 0.9rem;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <main class="card" role="main">
        <h1>Seu ambiente Docker está pronto!</h1>
        <p>Para visualizar um cardápio, acesse o endereço com o <strong>slug</strong> da empresa logo após o host.</p>
        <?php if ($companies === []): ?>
            <p class="hint">Cadastre uma empresa pelo painel administrativo e depois visite
                <code>http://localhost:8000/&lt;slug-da-empresa&gt;</code>.</p>
        <?php else: ?>
            <p class="hint">Exemplo: <code>http://localhost:8000/&lt;slug&gt;</code></p>
            <ul>
                <?php foreach ($companies as $company): ?>
                    <?php $name = (string)($company['name'] ?? ''); ?>
                    <?php $slug = (string)($company['slug'] ?? ''); ?>
                    <?php if ($slug === '') continue; ?>
                    <li>
                        <a href="/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($name !== '' ? $name : $slug, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <span class="hint">(slug: <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
