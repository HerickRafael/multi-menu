<?php
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$company = is_array($company ?? null) ? $company : [];
$methods = is_array($methods ?? null) ? $methods : [];
$flash   = is_array($flash ?? null) ? $flash : null;
$old     = is_array($old ?? null) ? $old : ['name' => '', 'instructions' => '', 'sort_order' => 0, 'active' => 1];
$errors  = is_array($errors ?? null) ? $errors : [];
$user    = $user ?? null;

$slug = rawurlencode((string)($company['slug'] ?? ''));
$title = $title ?? ('Métodos de pagamento - ' . ($company['name'] ?? ''));
$base  = base_url('admin/' . $slug . '/payment-methods');

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<header class="mb-6 flex items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
      <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2Zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 3H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/>
      <path d="M3 10a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 0 1H4a.5.5 0 0 0 0 1h1.5a.5.5 0 0 1 0 1H4a1 1 0 0 1-1-1z"/>
    </svg>
  </span>
  <div>
    <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">Métodos de pagamento</h1>
    <p class="text-sm text-slate-500">Cadastre as formas de pagamento disponíveis para o cliente escolher no checkout.</p>
  </div>
  <div class="ml-auto flex items-center gap-2">
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layout-text-window" viewBox="0 0 16 16">
        <path d="M1 2a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v11.5a1.5 1.5 0 0 1-1.5 1.5H2A2 2 0 0 1 0 13V2Zm14 1H1v10c0 .552.448 1 1 1h10.5a.5.5 0 0 0 .5-.5z"/>
      </svg>
      Dashboard
    </a>
  </div>
</header>

<?php if ($flash): ?>
  <div class="mb-4 rounded-xl border <?= ($flash['type'] ?? '') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> px-4 py-3 text-sm">
    <?= e($flash['message'] ?? '') ?>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
    <?php foreach ($errors as $message): ?>
      <div><?= e($message) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Adicionar novo método</h2>
    <p class="mb-4 text-sm text-slate-500">Defina o nome que será exibido para o cliente e, se necessário, descreva como o pagamento será realizado.</p>

    <form method="post" action="<?= e($base) ?>" class="grid gap-3">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Nome do método</span>
        <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Ex.: Pix, Dinheiro, Cartão" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
      </label>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Instruções (opcional)</span>
        <textarea name="instructions" rows="3" placeholder="Recados exibidos após a escolha do cliente" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= e($old['instructions'] ?? '') ?></textarea>
      </label>

      <div class="grid gap-3 sm:grid-cols-2">
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Ordem de exibição</span>
          <input type="number" name="sort_order" value="<?= e($old['sort_order'] ?? 0) ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </label>
        <label class="mt-6 inline-flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" name="active" value="1" <?= !empty($old['active']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
          <span>Disponível no checkout</span>
        </label>
      </div>

      <div class="flex gap-3">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-indigo-300 bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-500">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Adicionar método
        </button>
      </div>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Métodos cadastrados</h2>
    <?php if (!$methods): ?>
      <p class="text-sm text-slate-500">Ainda não há métodos cadastrados. Utilize o formulário ao lado para iniciar.</p>
    <?php endif; ?>
    <div class="mt-3 grid gap-4">
      <?php foreach ($methods as $method):
          $methodId = (int)($method['id'] ?? 0);
          $methodSlug = $base . '/' . $methodId;
          ?>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-inner">
          <form method="post" action="<?= e($methodSlug) ?>" class="grid gap-3">
            <?php if (function_exists('csrf_field')): ?>
              <?= csrf_field() ?>
            <?php elseif (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php endif; ?>
            <label class="grid gap-1 text-sm">
              <span class="font-semibold text-slate-700">Nome</span>
              <input type="text" name="name" value="<?= e($method['name'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
            </label>
            <label class="grid gap-1 text-sm">
              <span class="font-semibold text-slate-700">Instruções</span>
              <textarea name="instructions" rows="2" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Informações adicionais para o cliente"><?= e($method['instructions'] ?? '') ?></textarea>
            </label>
            <div class="grid gap-3 sm:grid-cols-3 sm:items-center">
              <label class="grid gap-1 text-sm">
                <span class="font-semibold text-slate-700">Ordem</span>
                <input type="number" name="sort_order" value="<?= e($method['sort_order'] ?? 0) ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              </label>
              <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="active" value="1" <?= !empty($method['active']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Exibir no checkout</span>
              </label>
              <div class="flex items-center justify-end gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-3 py-2 text-sm font-medium text-white shadow hover:bg-emerald-500">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Salvar
                </button>
              </div>
            </div>
          </form>
          <form method="post" action="<?= e($methodSlug . '/delete') ?>" class="mt-2" onsubmit="return confirm('Remover este método de pagamento?');">
            <?php if (function_exists('csrf_field')): ?>
              <?= csrf_field() ?>
            <?php elseif (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php endif; ?>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-3 py-2 text-sm text-red-600 shadow-sm hover:bg-red-50">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6h12M9 6l.867 10.4a1 1 0 0 0 .996.9h2.274a1 1 0 0 0 .996-.9L15 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 6V4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
              Excluir
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
