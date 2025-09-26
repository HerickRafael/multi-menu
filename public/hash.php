<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$password = '';
$hash = null;
$error = null;
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'POST') {
    $password = (string) ($_POST['password'] ?? '');

    if (trim($password) === '') {
        $error = 'Informe uma senha para gerar o hash.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($hash === false) {
            $error = 'Não foi possível gerar o hash. Tente novamente.';
            $hash = null;
        }
    }
}

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerador de hash de senha</title>
  <style>
    :root {
      color-scheme: light dark;
      font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background-color: #f8fafc;
      color: #0f172a;
    }

    body {
      margin: 0;
      padding: 2.5rem 1rem 3rem;
      display: flex;
      justify-content: center;
    }

    main {
      width: min(640px, 100%);
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #cbd5f5;
      border-radius: 1.25rem;
      padding: 2.5rem;
      box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.2);
      backdrop-filter: blur(12px);
    }

    h1 {
      margin-top: 0;
      font-size: clamp(1.5rem, 2vw + 1rem, 2.5rem);
      font-weight: 700;
      color: #1e293b;
    }

    p {
      color: #475569;
      line-height: 1.6;
    }

    form {
      margin-top: 2rem;
      display: grid;
      gap: 1rem;
    }

    label {
      font-size: 0.95rem;
      font-weight: 600;
      color: #334155;
    }

    input[type="text"] {
      width: 100%;
      border-radius: 0.9rem;
      border: 1px solid #cbd5f5;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      background: white;
    }

    input[type="text"]:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
    }

    button {
      justify-self: start;
      border: none;
      border-radius: 9999px;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: #fff;
      padding: 0.75rem 1.75rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 25px -10px rgba(79, 70, 229, 0.5);
    }

    .alert {
      margin-top: 1.5rem;
      padding: 1rem 1.25rem;
      border-radius: 1rem;
      border: 1px solid;
    }

    .alert-error {
      background: rgba(248, 113, 113, 0.12);
      border-color: rgba(248, 113, 113, 0.35);
      color: #b91c1c;
    }

    .result {
      margin-top: 2rem;
      padding: 1.5rem;
      border-radius: 1.1rem;
      border: 1px solid rgba(16, 185, 129, 0.4);
      background: rgba(236, 253, 245, 0.8);
    }

    textarea {
      width: 100%;
      margin-top: 0.75rem;
      border-radius: 0.85rem;
      border: 1px solid rgba(16, 185, 129, 0.4);
      background: #fff;
      padding: 1rem;
      font-size: 0.95rem;
      line-height: 1.5;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      resize: vertical;
      min-height: 120px;
    }

    footer {
      margin-top: 2.5rem;
      font-size: 0.85rem;
      color: #94a3b8;
      text-align: center;
    }

    @media (max-width: 640px) {
      body {
        padding: 1.5rem 1rem 2.5rem;
      }

      main {
        padding: 2rem 1.5rem;
      }
    }
  </style>
</head>
<body>
  <main>
    <h1>Gerador de hash de senha</h1>
    <p>Utilize este formulário para gerar rapidamente um hash seguro com <code>password_hash()</code> e armazená-lo no banco de dados dos administradores.</p>

    <?php if ($error !== null): ?>
      <div class="alert alert-error"><?= $escape($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
      <div>
        <label for="password-input">Senha</label>
        <input
          type="text"
          id="password-input"
          name="password"
          value="<?= $escape($password) ?>"
          placeholder="Digite a senha que deseja criptografar"
          required
        >
      </div>
      <button type="submit">Gerar hash</button>
    </form>

    <?php if ($hash !== null): ?>
      <section class="result">
        <h2 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #0f172a;">Hash gerado</h2>
        <p style="margin-top: 0.5rem; color: #047857;">Copie o valor abaixo e salve no campo <code>password_hash</code> do usuário.</p>
        <textarea readonly><?= $escape($hash) ?></textarea>
      </section>
    <?php endif; ?>

    <footer>
      Esta página é independente do painel administrativo e pode ser publicada em ambientes restritos para a equipe técnica.
    </footer>
  </main>
</body>
</html>
