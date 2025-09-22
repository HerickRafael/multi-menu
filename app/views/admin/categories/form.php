<?php
$title    = "Categoria - " . ($company['name'] ?? '');
$editing  = !empty($cat['id']);
$slug     = rawurlencode((string)($company['slug'] ?? ''));
$action   = $editing
              ? 'admin/' . $slug . '/categories/' . (int)$cat['id']
              : 'admin/' . $slug . '/categories';

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

ob_start(); ?>

<!-- HEADER -->
<header class="mb-6 flex items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
      <path d="M6 6h12v12H6z" stroke="currentColor" stroke-width="1.6"/>
    </svg>
  </span>
  <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">
    <?= $editing ? 'Editar' : 'Nova' ?> Categoria
  </h1>
</header>

<!-- FORM -->
<form method="post" action="<?= e(base_url($action)) ?>"
      class="grid gap-4 max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

  <!-- Nome -->
  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Nome</span>
    <input name="name" value="<?= e($cat['name'] ?? '') ?>"
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
           placeholder="Ex: Bebidas, Lanches">
  </label>

  <!-- Ordem -->
  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Ordem</span>
    <input name="sort_order" type="number" value="<?= e($cat['sort_order'] ?? 0) ?>"
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
           min="0">
    <span class="text-xs text-slate-500">Define a posição da categoria na lista.</span>
  </label>

  <!-- Ativa -->
  <label class="inline-flex items-center gap-2">
    <input type="checkbox" name="active"
           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           <?= !isset($cat['active']) || $cat['active'] ? 'checked' : '' ?>>
    <span class="text-sm text-slate-700">Ativa</span>
  </label>

  <!-- Botões -->
  <div class="mt-2 flex gap-3">
    <button class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Salvar
    </button>
    <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
      Cancelar
    </a>
  </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
