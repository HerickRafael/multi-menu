<?php

$company   = is_array($company ?? null) ? $company : [];
$customer  = is_array($customer ?? null) ? $customer : [];
$addresses = is_array($addresses ?? null) ? $addresses : [];

$slug      = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slugClean = trim($slug, '/');

$homeUrl   = function_exists('base_url') ? base_url($slugClean) : '#';
$cartUrl   = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'cart') : '#';
$profileUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile') : '#';
$logoutUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'customer-logout') : '#';
$updateUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile/update') : '#';
$addressUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'profile/address') : '#';

$title = 'Perfil — ' . e($company['name'] ?? 'Cardápio');
$showFooterMenu = false;

ob_start();
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= $title ?></title>
<style>
  :root{ --bg:#f3f4f6; --card:#ffffff; --border:#e5e7eb; --muted:#6b7280; --text:#0f172a; --accent:#f59e0b; --accent-active:#d97706; --accent-ink:#ffffff; }
  *{box-sizing:border-box;font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
  body{margin:0;background:var(--bg);color:var(--text);}
  .app{width:100%;max-width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column;padding-bottom:120px;background:var(--bg);}
  @media (min-width:768px){ .app{max-width:420px;} }
  .topbar{position:sticky;top:0;background:var(--card);border-bottom:1px solid var(--border);z-index:10;}
  .topwrap{display:flex;align-items:center;gap:12px;padding:12px 16px;}
  .back{width:36px;height:36px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;background:var(--card);cursor:pointer;}
  .back svg{width:18px;height:18px;}
  .title{font-weight:800;font-size:18px;}
  .content{flex:1;padding:16px 16px 0 16px;display:grid;gap:16px;}
  .card{background:var(--card);border-radius:18px;border:1px solid var(--border);padding:18px;display:grid;gap:14px;box-shadow:0 10px 30px -18px rgba(15,23,42,.35);}
  .card h2{margin:0;font-size:18px;font-weight:700;}
  .card p.description{margin:0;font-size:13px;color:var(--muted);}
  label.field{display:grid;gap:6px;font-size:13px;color:#111827;}
  label.field span{font-weight:600;}
  .field input,.field textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:12px 14px;font-size:15px;background:#f9fafb;transition:border-color .2s ease,box-shadow .2s ease;}
  .field input:focus,.field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,158,11,.2);outline:none;background:#fff;}
  .grid-two{display:grid;gap:12px;}
  @media (min-width:480px){ .grid-two{grid-template-columns:repeat(2,minmax(0,1fr));} }
  .tag{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:600;}
  .addresses{display:grid;gap:12px;}
  .address-card{border:1px solid var(--border);border-radius:16px;padding:14px;background:var(--card);display:grid;gap:8px;box-shadow:0 8px 20px -16px rgba(15,23,42,.35);}
  .address-title{font-weight:700;font-size:15px;color:#111827;display:flex;justify-content:space-between;align-items:center;}
  .address-meta{font-size:13px;color:var(--muted);}
  .address-actions{display:flex;gap:8px;}
  .ghost-btn{flex:1;border:1px solid var(--border);background:#fff;border-radius:12px;padding:10px;font-weight:600;font-size:13px;color:#1f2937;cursor:pointer;}
  .ghost-btn:active{background:#f3f4f6;}
  .cta{display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;border:none;border-radius:18px;padding:0 24px;background:var(--accent);color:var(--accent-ink);font-weight:800;font-size:16px;text-decoration:none;text-align:center;cursor:pointer;}
  .cta:active{background:var(--accent-active);}
  .cta[disabled]{opacity:.6;cursor:not-allowed;}
  .footer{position:fixed;left:50%;transform:translateX(-50%);bottom:0;width:100%;max-width:100%;background:var(--card);border-top:1px solid var(--border);padding:12px 16px 18px;box-shadow:0 -10px 30px -18px rgba(15,23,42,.35);z-index:40;}
  @media (min-width:768px){ .footer{max-width:420px;} }
</style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topwrap">
  <button class="back" type="button" data-action="navigate" data-href="<?= e($homeUrl) ?>" aria-label="Voltar">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
      </button>
      <div class="title">Perfil</div>
    </div>
  </div>

  <main class="content">
    <section class="card">
      <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <h2>Dados pessoais</h2>
        <span class="tag">Atualize seus dados</span>
      </div>
      <form method="post" action="<?= e($updateUrl) ?>" class="grid gap-12">
        <?php if (function_exists('csrf_field')): ?>
          <?= csrf_field() ?>
        <?php elseif (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php endif; ?>

        <div class="grid gap-12">
          <label class="field">
            <span>Nome completo</span>
            <input type="text" name="profile[name]" value="<?= e($customer['name'] ?? '') ?>" placeholder="Seu nome" required>
          </label>
          <label class="field">
            <span>WhatsApp</span>
            <input type="tel" name="profile[whatsapp]" value="<?= e($customer['whatsapp'] ?? '') ?>" placeholder="(11) 90000-0000" required>
          </label>
          <label class="field">
            <span>E-mail</span>
            <input type="email" name="profile[email]" value="<?= e($customer['email'] ?? '') ?>" placeholder="voce@exemplo.com">
          </label>
          <div class="grid-two">
            <label class="field">
              <span>Data de aniversário</span>
              <input type="date" name="profile[birthdate]" value="<?= e($customer['birthdate'] ?? '') ?>">
            </label>
            <label class="field">
              <span>Documento (CPF)</span>
              <input type="text" name="profile[document]" value="<?= e($customer['document'] ?? '') ?>" placeholder="000.000.000-00">
            </label>
          </div>
          <label class="field">
            <span>Observações para o pedido</span>
            <textarea name="profile[notes]" rows="3" placeholder="Detalhes para o atendimento"><?= e($customer['notes'] ?? '') ?></textarea>
          </label>
        </div>

        <div class="grid gap-8">
          <button class="cta" type="submit">Salvar alterações</button>
          <form method="post" action="<?= e($logoutUrl) ?>" style="margin:0;">
            <?php if (function_exists('csrf_field')) {
                echo csrf_field();
            } ?>
            <button class="ghost-btn" type="submit" style="width:100%;">Sair da conta</button>
          </form>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Endereços de entrega</h2>
      <p class="description">Gerencie seus locais de entrega preferidos para agilizar os pedidos.</p>
      <div class="addresses">
        <?php if ($addresses): ?>
          <?php foreach ($addresses as $address): ?>
            <article class="address-card">
              <div class="address-title">
                <span><?= e($address['label'] ?? 'Endereço') ?></span>
                <?php if (!empty($address['default'])): ?><span class="tag">Principal</span><?php endif; ?>
              </div>
              <div class="address-meta">
                <?= e($address['street'] ?? '') ?>, <?= e($address['number'] ?? '') ?> <?= e($address['complement'] ? ' - '.$address['complement'] : '') ?><br>
                <?= e($address['neighborhood'] ?? '') ?> · <?= e($address['city'] ?? '') ?> / <?= e($address['state'] ?? '') ?>
              </div>
              <?php if (!empty($address['reference'])): ?>
                <div class="address-meta">Referência: <?= e($address['reference']) ?></div>
              <?php endif; ?>
              <div class="address-actions">
                <button class="ghost-btn" type="button" data-action="navigate" data-href="<?= e($addressUrl . '/edit/' . (int)($address['id'] ?? 0)) ?>">Editar</button>
                <button class="ghost-btn" type="button" data-action="confirm-navigate" data-message="Remover este endereço?" data-href="<?= e($addressUrl . '/remove/' . (int)($address['id'] ?? 0)) ?>">Remover</button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="address-card" style="text-align:center;">
            <div class="address-title" style="justify-content:center;">Nenhum endereço salvo</div>
            <div class="address-meta">Adicione um endereço para agilizar o checkout.</div>
          </div>
        <?php endif; ?>
      </div>
  <button class="ghost-btn" type="button" data-action="navigate" data-href="<?= e($addressUrl . '/create') ?>" style="margin-top:8px;">Adicionar novo endereço</button>
    </section>

    <section class="card">
      <h2>Configurações de pedido</h2>
      <div class="grid-two">
        <label class="field">
          <span>Forma de pagamento favorita</span>
          <input type="text" name="preferences[payment]" value="<?= e($customer['preferred_payment'] ?? '') ?>" placeholder="Ex.: Pix, Cartão...">
        </label>
        <label class="field">
          <span>Canal de contato preferido</span>
          <input type="text" name="preferences[contact]" value="<?= e($customer['preferred_contact'] ?? 'WhatsApp') ?>" placeholder="WhatsApp, Telefone...">
        </label>
      </div>
      <label class="field">
        <span>Notas gerais</span>
        <textarea name="preferences[general_notes]" rows="3" placeholder="Algum pedido recorrente ou restrição?"><?= e($customer['general_notes'] ?? '') ?></textarea>
      </label>
      <button class="cta" type="submit" form="profile-preferences" disabled>Funcionalidade em desenvolvimento</button>
    </section>
  </main>

  <div class="footer">
  <button class="cta" type="button" data-action="navigate" data-href="<?= e($homeUrl) ?>">Voltar aos produtos</button>
  </div>
</div>

</body>
</html>
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
