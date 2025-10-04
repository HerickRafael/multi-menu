<?php
$title    = "Categoria - " . ($company['name'] ?? '');
$editing  = !empty($cat['id']);
$slug     = rawurlencode((string)($company['slug'] ?? ''));
$action   = $editing
  ? "admin/{$slug}/categories/" . (int)$cat['id']
  : "admin/{$slug}/categories";

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

ob_start(); ?>

<div class="mx-auto max-w-4xl p-4">

<form id="category-form"
      method="post"
      action="<?= e(base_url($action)) ?>"
      class="relative grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- TOOLBAR FIXA (idêntica ao bloco de Produto) -->
  <div class="sticky top-0 z-20 -m-4 mb-0 border-b bg-white/85 px-4 py-2 backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto flex max-w-4xl items-center justify-between">
      <div class="flex items-center gap-2 text-sm text-slate-800">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
          <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <strong><?= $editing ? 'Editar' : 'Nova' ?> categoria</strong>
      </div>
      <div class="flex gap-2">
        <a href="<?= e(base_url("admin/{$slug}/categories")) ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-1.5 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Salvar
        </button>
      </div>
    </div>
  </div>

  <!-- CAMPOS -->
  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Nome</span>
    <input name="name" value="<?= e($cat['name'] ?? '') ?>" required
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
           placeholder="Ex: Bebidas, Lanches">
  </label>

  <label class="grid gap-1">
    <span class="text-sm font-medium text-slate-700">Ordem</span>
    <input name="sort_order" type="number" min="0" step="1" value="<?= e($cat['sort_order'] ?? 0) ?>"
           class="rounded-xl border border-slate-300 px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
           placeholder="0">
    <span class="text-xs text-slate-500">Define a posição da categoria na lista.</span>
  </label>

  <label class="inline-flex items-center gap-2 pt-1">
    <input type="checkbox" name="active"
           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
           <?= !isset($cat['active']) || $cat['active'] ? 'checked' : '' ?>>
    <span class="text-sm text-slate-700">Ativa</span>
  </label>

  <!-- rodapé interno só pra respiro -->
  <div class="pb-1"></div>

</form>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
