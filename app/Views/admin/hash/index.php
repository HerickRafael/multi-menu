<?php
/** @var string|null $hash */
/** @var string|null $error */

$title = 'Gerador de hash';
$company = null;

ob_start();
?>
<div class="max-w-2xl mx-auto space-y-6">
  <header class="space-y-2">
    <h1 class="text-3xl font-bold text-slate-900">Gerador de hash de senha</h1>
    <p class="text-slate-600">Digite a senha desejada e gere um hash seguro com <code class="font-mono">password_hash()</code> para utilizar no banco de dados.</p>
  </header>

  <?php if (!empty($error)): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="space-y-2">
      <label class="block text-sm font-medium text-slate-700" for="password-input">Senha</label>
      <input
        id="password-input"
        name="password"
        type="text"
        required
        autocomplete="off"
        class="w-full rounded-xl border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200"
        placeholder="Digite a senha que deseja armazenar"
      >
      <p class="text-xs text-slate-500">O valor digitado não é armazenado; ele é usado apenas para gerar o hash abaixo.</p>
    </div>

    <button class="rounded-xl bg-slate-900 px-4 py-2 font-semibold text-white transition hover:bg-slate-800" type="submit">
      Gerar hash
    </button>
  </form>

  <?php if (!empty($hash)): ?>
    <section class="space-y-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-900">
      <h2 class="text-lg font-semibold">Hash gerado</h2>
      <p class="text-sm text-emerald-800">Copie o valor abaixo e salve no campo <code class="font-mono">password_hash</code> do usuário.</p>
      <textarea class="w-full rounded-xl border border-emerald-300 bg-white p-3 font-mono text-sm" rows="3" readonly><?= e($hash) ?></textarea>
    </section>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/../layout.php';
