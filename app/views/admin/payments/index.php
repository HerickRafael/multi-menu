<?php
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('render_payment_method_row')) {
    function render_payment_method_row(array $method, array $pixTypeLabels, string $base)
    {
        $method = is_array($method) ? $method : [];
        $methodId = (int)($method['id'] ?? 0);
        $type = $method['type'] ?? 'others';
        $meta = is_array($method['meta'] ?? null) ? $method['meta'] : [];
        $isActive = !empty($method['active']);
        $methodJson = htmlspecialchars(json_encode($method, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $trackClass = $isActive ? 'bg-rose-500' : 'bg-slate-200';
        $thumbTransform = $isActive ? 'translateX(20px)' : 'translateX(0)';
        $typeBadgeClass = $type === 'pix' ? 'bg-emerald-100 text-emerald-700' : ($type === 'credit' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700');
        $typeLabel = ucfirst($type);

        ob_start();
        ?>
        <div class="pm-row flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3" data-id="<?= $methodId ?>" data-type="<?= e($type) ?>" data-method="<?= $methodJson ?>">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200">
              <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M7 11h10M5 15h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <div class="font-semibold text-slate-800"><?= e($method['name'] ?? '') ?></div>
                <div class="text-xs rounded-full px-2 py-1 <?= $typeBadgeClass ?>"><?= e($typeLabel) ?></div>
              </div>
              <?php if ($type === 'pix'): ?>
                <?php if (!empty($meta['px_key'])): ?><div class="text-xs text-slate-500">Chave Pix: <?= e($meta['px_key']) ?></div><?php endif; ?>
                <?php if (!empty($meta['px_key_type'])): ?><div class="text-xs text-slate-500">Tipo da chave: <?= e($pixTypeLabels[$meta['px_key_type']] ?? ucfirst($meta['px_key_type'])) ?></div><?php endif; ?>
                <?php if (!empty($meta['px_holder_name'])): ?><div class="text-xs text-slate-500">Titular: <?= e($meta['px_holder_name']) ?></div><?php endif; ?>
              <?php endif; ?>
              <div class="text-xs text-slate-500">ID #<?= $methodId ?></div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="inline-flex items-center cursor-pointer">
              <input data-id="<?= $methodId ?>" type="checkbox" class="pm-toggle sr-only" <?= $isActive ? 'checked' : '' ?> />
              <span class="pm-toggle-track w-10 h-6 <?= $trackClass ?> rounded-full relative transition-colors">
                <span class="pm-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: <?= $thumbTransform ?>"></span>
              </span>
            </label>
            <button type="button" class="pm-edit text-sm text-slate-500" data-id="<?= $methodId ?>">Editar</button>
          </div>
        </div>
        <?php
        return trim((string)ob_get_clean());
    }
}

$company = is_array($company ?? null) ? $company : [];
$methods = is_array($methods ?? null) ? $methods : [];
$flash   = is_array($flash ?? null) ? $flash : null;
$old     = is_array($old ?? null) ? $old : ['name' => '', 'instructions' => '', 'sort_order' => 0, 'active' => 1, 'type' => 'credit', 'meta' => []];
$errors  = is_array($errors ?? null) ? $errors : [];
$user    = $user ?? null;

$old['meta'] = is_array($old['meta'] ?? null) ? $old['meta'] : [];
$oldType = is_string($old['type'] ?? null) ? $old['type'] : 'credit';
$allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix'];
if (!in_array($oldType, $allowedTypes, true)) {
    $oldType = 'credit';
}

// --- Normalizações e labels Pix (mantidos do branch com melhorias)
$pixTypeLabels = [
    'email' => 'E-mail',
    'cpf' => 'CPF',
    'cnpj' => 'CNPJ',
    'telefone' => 'Telefone',
    'aleatoria' => 'Chave aleatória',
];

$normaliseMeta = function ($meta) {
    if (is_string($meta)) {
        $decoded = json_decode($meta, true);
        $meta = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($meta)) {
        return [];
    }

    $clean = [];
    foreach ($meta as $k => $v) {
        if (!is_string($k)) {
            continue;
        }
        $value = trim((string)$v);
        if ($value === '') {
            continue;
        }
        $clean[$k] = $value;
    }

    return $clean;
};

foreach ($methods as &$methodItem) {
    $methodItem = is_array($methodItem) ? $methodItem : [];
    $methodItem['meta'] = $normaliseMeta($methodItem['meta'] ?? []);
    $methodItem['type'] = is_string($methodItem['type'] ?? null) ? $methodItem['type'] : 'others';
    $methodItem['name'] = (string)($methodItem['name'] ?? '');
    $methodItem['instructions'] = (string)($methodItem['instructions'] ?? '');
    $methodItem['sort_order'] = isset($methodItem['sort_order']) ? (int)$methodItem['sort_order'] : 0;
    $methodItem['active'] = !empty($methodItem['active']) ? 1 : 0;
}
unset($methodItem);

$pixMethods = array_filter($methods, fn($m) => ($m['type'] ?? '') === 'pix');
$otherMethods = array_filter($methods, fn($m) => ($m['type'] ?? '') !== 'pix');

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
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        const type = document.getElementById('pm-type');
        const pixFields = document.getElementById('pm-pix-fields');
        const nameField = document.getElementById('pm-name-field');
        const nameInput = document.getElementById('pm-name');

        function togglePixFields(){
          if (!type) return;
          const isPix = type.value === 'pix';
          if (pixFields) {
            pixFields.classList.toggle('hidden', !isPix);
          }
          if (nameField) {
            nameField.classList.toggle('hidden', isPix);
          }
          if (nameInput) {
            if (!nameInput.dataset.originalRequired) {
              nameInput.dataset.originalRequired = nameInput.hasAttribute('required') ? '1' : '0';
            }
            if (isPix) {
              nameInput.removeAttribute('required');
              if (!nameInput.value) {
                nameInput.value = 'Pix';
              }
            } else if (nameInput.dataset.originalRequired === '1') {
              nameInput.setAttribute('required', 'required');
            }
          }
          if (typeof window.pmUpdatePixFeedback === 'function') {
            window.pmUpdatePixFeedback();
          }
        }

        window.pmTogglePixFields = togglePixFields;

        if (type){
          type.addEventListener('change', togglePixFields);
          togglePixFields();
        }
      });
    </script>
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

  <form method="post" action="<?= e($base) ?>" class="grid gap-3" id="pm-create-form">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <label id="pm-name-field" class="grid gap-1 text-sm <?= $oldType === 'pix' ? 'hidden' : '' ?>">
        <span class="font-semibold text-slate-700">Nome da bandeira</span>
        <input id="pm-name" type="text" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Ex.: Visa, MasterCard, Pix, Dinheiro" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required autofocus aria-describedby="pm-name-help">
        <div id="pm-name-help" class="text-xs text-slate-400">Nome exibido ao cliente no checkout (ex.: Visa, Pix).</div>
      </label>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Tipo</span>
        <select name="type" id="pm-type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          <option value="credit" <?= $oldType === 'credit' ? 'selected' : '' ?>>Crédito</option>
          <option value="debit" <?= $oldType === 'debit' ? 'selected' : '' ?>>Débito</option>
          <option value="others" <?= $oldType === 'others' ? 'selected' : '' ?>>Outros</option>
          <option value="voucher" <?= $oldType === 'voucher' ? 'selected' : '' ?>>Vale-refeição</option>
          <option value="pix" <?= $oldType === 'pix' ? 'selected' : '' ?>>Pix</option>
        </select>
      </label>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Instruções (opcional)</span>
        <textarea name="instructions" rows="3" placeholder="Recados exibidos após a escolha do cliente" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= e($old['instructions'] ?? '') ?></textarea>
      </label>

      <div id="pm-pix-fields" class="<?= $oldType === 'pix' ? 'grid gap-2' : 'hidden grid gap-2' ?>">
        <h3 class="text-sm font-semibold">Credenciais Pix</h3>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Chave Pix</span>
          <input id="pm-pix-key" type="text" name="meta[px_key]" value="<?= e($old['meta']['px_key'] ?? '') ?>" placeholder="Ex.: 11999999999 ou chave aleatória" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
          <div id="pm-pix-key-feedback" class="text-xs text-slate-400">
            <?php if (!empty($old['meta']['px_key_type'])): ?>
              Tipo identificado: <?= e($pixTypeLabels[$old['meta']['px_key_type']] ?? ucfirst($old['meta']['px_key_type'])) ?>
            <?php else: ?>
              Informe a chave para identificar automaticamente o tipo.
            <?php endif; ?>
          </div>
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Provedor (opcional)</span>
          <input type="text" name="meta[px_provider]" value="<?= e($old['meta']['px_provider'] ?? '') ?>" placeholder="Ex.: Gerencianet, Pagar.me" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Nome do titular Pix</span>
          <input type="text" name="meta[px_holder_name]" value="<?= e($old['meta']['px_holder_name'] ?? '') ?>" placeholder="Ex.: João da Silva" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>
      </div>

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

      <input type="hidden" name="method_id" id="pm-method-id" value="<?= isset($old['id']) ? (int)$old['id'] : '' ?>">

      <div class="flex gap-3">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <span id="pm-submit-label">Adicionar método</span>
        </button>
        <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Cancelar</a>
      </div>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Métodos cadastrados</h2>
    <div class="mt-3 flex items-center justify-between">
      <div class="text-sm font-medium text-slate-600">Gerencie os métodos cadastrados abaixo.</div>
      <div class="flex items-center gap-3 text-sm text-slate-600">
        <span>Ativar todas</span>
        <label class="inline-flex items-center cursor-pointer">
          <input id="pm-toggle-all" type="checkbox" class="sr-only">
          <span class="pm-toggle-all-track w-10 h-6 bg-slate-200 rounded-full relative transition-colors">
            <span class="pm-toggle-all-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(0)"></span>
          </span>
        </label>
      </div>
    </div>

    <div class="mt-4 space-y-4">
      <div id="pm-pix-block" class="<?= $pixMethods ? '' : 'hidden' ?>">
        <h3 class="mb-2 text-sm font-semibold text-slate-700">Pix</h3>
        <div class="space-y-2" id="pm-pix-list">
          <?php foreach ($pixMethods as $method): ?>
            <?= render_payment_method_row($method, $pixTypeLabels, $base) ?>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="space-y-2" id="pm-list">
        <?php foreach ($otherMethods as $method): ?>
          <?= render_payment_method_row($method, $pixTypeLabels, $base) ?>
        <?php endforeach; ?>
      </div>

      <div id="pm-empty" class="<?= $methods ? 'hidden' : 'text-sm text-slate-500' ?>">
        Ainda não há métodos cadastrados. Utilize o formulário ao lado para iniciar.
      </div>
    </div>

        <script>
      (function(){
        const base = '<?= $base ?>';
        const csrftoken = <?= function_exists('csrf_token') ? ('"' . addslashes(csrf_token()) . '"') : 'null' ?>;
        const list = document.getElementById('pm-list');
        const pixList = document.getElementById('pm-pix-list');
        const pixBlock = document.getElementById('pm-pix-block');
        const emptyMessage = document.getElementById('pm-empty');
        const toggleAll = document.getElementById('pm-toggle-all');
        const toggleAllTrack = document.querySelector('.pm-toggle-all-track');
        const toggleAllThumb = document.querySelector('.pm-toggle-all-thumb');
        const form = document.getElementById('pm-create-form');
        const typeSelect = document.getElementById('pm-type');
        const nameInput = document.getElementById('pm-name');
        const pixKeyInput = document.getElementById('pm-pix-key');
        const pixKeyFeedback = document.getElementById('pm-pix-key-feedback');
        const pixProviderInput = document.querySelector('input[name="meta[px_provider]"]');
        const pixHolderInput = document.querySelector('input[name="meta[px_holder_name]"]');
        const methodIdInput = document.getElementById('pm-method-id');
        const submitLabel = document.getElementById('pm-submit-label');
        const instructionsInput = document.querySelector('textarea[name="instructions"]');
        const sortOrderInput = document.querySelector('input[name="sort_order"]');
        const activeInput = document.querySelector('input[name="active"]');
        let editingId = null;
        let defaultSortOrder = sortOrderInput ? sortOrderInput.value : '';

        const pixTypeLabels = <?= json_encode($pixTypeLabels, JSON_UNESCAPED_UNICODE) ?>;

        function detectPixKeyType(key) {
          key = (key || '').trim();
          if (!key) return '';
          if (/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(key)) return 'email';
          const digits = key.replace(/\D+/g, '');
          if (digits.length === 11) return 'cpf';
          if (digits.length === 14) return 'cnpj';
          if (digits.length >= 10 && digits.length <= 13) return 'telefone';
          return 'aleatoria';
        }

        function formatPixKeyType(type) {
          if (!type) return '';
          return pixTypeLabels[type] || (type.charAt(0).toUpperCase() + type.slice(1));
        }

        function escapeHtml(value) {
          return (value == null ? '' : String(value))
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        function updatePixKeyFeedback() {
          if (!pixKeyFeedback) return;
          const key = pixKeyInput ? pixKeyInput.value : '';
          if (!key) {
            pixKeyFeedback.textContent = 'Informe a chave para identificar automaticamente o tipo.';
            return;
          }
          const detected = detectPixKeyType(key);
          pixKeyFeedback.textContent = 'Tipo identificado: ' + formatPixKeyType(detected || 'aleatoria');
        }

        window.pmUpdatePixFeedback = updatePixKeyFeedback;

        if (pixKeyInput) {
          pixKeyInput.addEventListener('input', updatePixKeyFeedback);
          updatePixKeyFeedback();
        }

        function updateEmptyStates() {
          if (pixBlock && pixList) {
            pixBlock.classList.toggle('hidden', pixList.children.length === 0);
          }
          if (emptyMessage && list) {
            const total = list.children.length + (pixList ? pixList.children.length : 0);
            emptyMessage.classList.toggle('hidden', total > 0);
          }
        }

        function setToggleVisual(track, on) {
          if (!track) return;
          track.classList.toggle('bg-rose-500', !!on);
          track.classList.toggle('bg-slate-200', !on);
          const thumb = track.querySelector('.pm-toggle-thumb');
          if (thumb) {
            thumb.style.transform = on ? 'translateX(20px)' : 'translateX(0)';
          }
        }

        function setToggleAllVisual(on) {
          if (!toggleAllTrack || !toggleAllThumb) return;
          toggleAllTrack.classList.toggle('bg-rose-500', !!on);
          toggleAllTrack.classList.toggle('bg-slate-200', !on);
          toggleAllThumb.style.transform = on ? 'translateX(20px)' : 'translateX(0)';
        }

        function refreshToggleAllState() {
          if (!toggleAll) return;
          const toggles = document.querySelectorAll('.pm-row .pm-toggle');
          if (!toggles.length) {
            toggleAll.checked = false;
            setToggleAllVisual(false);
            return;
          }
          const allChecked = Array.from(toggles).every(chk => chk.checked);
          toggleAll.checked = allChecked;
          setToggleAllVisual(allChecked);
        }

        function parseMethodData(node) {
          if (!node) return null;
          try {
            return JSON.parse(node.dataset.method || '{}');
          } catch (err) {
            return null;
          }
        }

        function wireRowInteractions(row) {
          if (!row) return;
          const checkbox = row.querySelector('.pm-toggle');
          if (checkbox && !checkbox.dataset.wired) {
            checkbox.dataset.wired = '1';
            checkbox.addEventListener('change', function(){
              const id = this.dataset.id;
              const on = this.checked;
              const track = row.querySelector('.pm-toggle-track');
              setToggleVisual(track, on);
              toggleMethod(id, on, row, function(success){
                if (!success) {
                  checkbox.checked = !on;
                  setToggleVisual(track, checkbox.checked);
                }
              });
            });
          }

          const editBtn = row.querySelector('.pm-edit');
          if (editBtn && !editBtn.dataset.wired) {
            editBtn.dataset.wired = '1';
            editBtn.addEventListener('click', function(){
              const data = parseMethodData(row);
              if (data) {
                fillFormWithMethod(data);
              }
            });
          }
        }

        function createPixInfo(method) {
          if (method.type !== 'pix' || !method.meta) return '';
          let html = '';
          if (method.meta.px_key) {
            html += `
                <div class="text-xs text-slate-500">Chave Pix: ${escapeHtml(method.meta.px_key)}</div>`;
          }
          if (method.meta.px_key_type) {
            html += `
                <div class="text-xs text-slate-500">Tipo da chave: ${escapeHtml(formatPixKeyType(method.meta.px_key_type))}</div>`;
          }
          if (method.meta.px_holder_name) {
            html += `
                <div class="text-xs text-slate-500">Titular: ${escapeHtml(method.meta.px_holder_name)}</div>`;
          }
          return html;
        }

        function renderMethodRow(method) {
          const id = parseInt(method.id, 10);
          const type = method.type || 'others';
          const isActive = parseInt(method.active, 10) === 1;
          const div = document.createElement('div');
          div.className = 'pm-row flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3';
          div.dataset.id = String(id);
          div.dataset.type = type;
          div.dataset.method = JSON.stringify(method);
          const typeBadge = type === 'pix' ? 'bg-emerald-100 text-emerald-700' : (type === 'credit' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700');
          div.innerHTML = `
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200">
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M7 11h10M5 15h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <div class="font-semibold text-slate-800">${escapeHtml(method.name || '')}</div>
                  <div class="text-xs rounded-full px-2 py-1 ${typeBadge}">${escapeHtml(type.charAt(0).toUpperCase() + type.slice(1))}</div>
                </div>${createPixInfo(method)}
                <div class="text-xs text-slate-500">ID #${id}</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center cursor-pointer">
                <input data-id="${id}" type="checkbox" class="pm-toggle sr-only" ${isActive ? 'checked' : ''} />
                <span class="pm-toggle-track w-10 h-6 ${isActive ? 'bg-rose-500' : 'bg-slate-200'} rounded-full relative transition-colors">
                  <span class="pm-toggle-thumb absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: ${isActive ? 'translateX(20px)' : 'translateX(0)'}"></span>
                </span>
              </label>
              <button type="button" class="pm-edit text-sm text-slate-500" data-id="${id}">Editar</button>
            </div>
          `;
          wireRowInteractions(div);
          return div;
        }

        function replaceMethodRow(method) {
          const id = String(method.id);
          const newRow = renderMethodRow(method);
          const existing = document.querySelector('.pm-row[data-id="' + id + '"]');
          const target = method.type === 'pix' ? pixList : list;
          if (existing && existing.parentNode) {
            const parent = existing.parentNode;
            const nextSibling = existing.nextSibling;
            existing.parentNode.removeChild(existing);
            if (target && parent === target && nextSibling) {
              target.insertBefore(newRow, nextSibling);
            } else if (target && parent === target) {
              target.appendChild(newRow);
            } else if (target) {
              target.insertBefore(newRow, target.firstChild);
            }
          } else if (target) {
            target.insertBefore(newRow, target.firstChild);
          }
          updateEmptyStates();
          refreshToggleAllState();
        }

        function fillFormWithMethod(method) {
          if (!form) return;
          editingId = parseInt(method.id, 10) || null;
          form.action = editingId ? (base + '/' + editingId) : base;
          if (methodIdInput) {
            methodIdInput.value = editingId ? editingId : '';
          }
          if (nameInput) {
            nameInput.value = method.type === 'pix' ? 'Pix' : (method.name || '');
          }
          if (instructionsInput) {
            instructionsInput.value = method.instructions || '';
          }
          if (sortOrderInput) {
            sortOrderInput.value = method.sort_order != null ? method.sort_order : '';
          }
          if (activeInput) {
            activeInput.checked = parseInt(method.active, 10) === 1;
          }
          if (typeSelect) {
            typeSelect.value = method.type || 'others';
          }
          if (pixKeyInput) {
            pixKeyInput.value = method.meta && method.meta.px_key ? method.meta.px_key : '';
          }
          if (pixProviderInput) {
            pixProviderInput.value = method.meta && method.meta.px_provider ? method.meta.px_provider : '';
          }
          if (pixHolderInput) {
            pixHolderInput.value = method.meta && method.meta.px_holder_name ? method.meta.px_holder_name : '';
          }
          if (submitLabel) {
            submitLabel.textContent = 'Atualizar método';
          }
          if (typeof window.pmTogglePixFields === 'function') {
            window.pmTogglePixFields();
          }
          updatePixKeyFeedback();
          if (form.scrollIntoView) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }

        function resetForm(nextSortOrder) {
          if (!form) return;
          editingId = null;
          form.action = base;
          if (submitLabel) {
            submitLabel.textContent = 'Adicionar método';
          }
          if (methodIdInput) {
            methodIdInput.value = '';
          }
          if (nameInput) {
            nameInput.value = '';
          }
          if (instructionsInput) {
            instructionsInput.value = '';
          }
          if (sortOrderInput) {
            sortOrderInput.value = nextSortOrder !== undefined ? nextSortOrder : defaultSortOrder;
          }
          if (activeInput) {
            activeInput.checked = true;
          }
          if (typeSelect) {
            typeSelect.value = 'credit';
          }
          if (pixKeyInput) {
            pixKeyInput.value = '';
          }
          if (pixProviderInput) {
            pixProviderInput.value = '';
          }
          if (pixHolderInput) {
            pixHolderInput.value = '';
          }
          if (typeof window.pmTogglePixFields === 'function') {
            window.pmTogglePixFields();
          }
          updatePixKeyFeedback();
        }

        function applyToggleStateToRow(row, on) {
          if (!row) return;
          const checkbox = row.querySelector('.pm-toggle');
          if (checkbox) {
            checkbox.checked = !!on;
          }
          const track = row.querySelector('.pm-toggle-track');
          setToggleVisual(track, on);
        }

        async function toggleMethod(id, on, row, cb) {
          try {
            const url = base + '/' + id;
            const body = new URLSearchParams();
            body.append('active', on ? '1' : '0');
            if (csrftoken) body.append('csrf_token', csrftoken);
            const res = await fetch(url, {
              method: 'POST',
              body,
              credentials: 'same-origin',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });
            if (!res.ok) throw new Error('Network');
            const json = await res.json().catch(() => null);
            if (!json || !json.success) throw new Error('Invalid response');
            if (json.method) {
              replaceMethodRow(json.method);
            } else if (row) {
              applyToggleStateToRow(row, on);
            }
            if (cb) cb(true);
            return true;
          } catch (err) {
            console.error(err);
            alert('Erro ao atualizar o método. Atualize a página e tente novamente.');
            if (cb) cb(false);
            return false;
          } finally {
            refreshToggleAllState();
          }
        }

        if (toggleAll) {
          toggleAll.addEventListener('change', async function(){
            const on = this.checked;
            setToggleAllVisual(on);
            try {
              const url = base + '/batch';
              const body = new URLSearchParams();
              body.append('active', on ? '1' : '0');
              if (csrftoken) body.append('csrf_token', csrftoken);
              const res = await fetch(url, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });
              if (!res.ok) throw new Error('Network');
              const json = await res.json().catch(() => null);
              if (!json || !json.success) throw new Error('Batch failed');
              document.querySelectorAll('.pm-row').forEach(function(row){
                applyToggleStateToRow(row, on);
              });
            } catch (err) {
              console.error(err);
              alert('Erro ao atualizar todos os métodos. Atualize a página e tente novamente.');
              this.checked = !on;
              setToggleAllVisual(this.checked);
            }
          });
        }

        if (form) {
          form.addEventListener('submit', async function(e){
            e.preventDefault();
            const data = new FormData(form);
            if (!data.has('active')) {
              data.set('active', '0');
            }
            const editing = methodIdInput ? methodIdInput.value : '';
            try {
              const res = await fetch(form.action, {
                method: form.method || 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });
              if (!res.ok) throw new Error('Network');
              const json = await res.json().catch(() => null);
              if (!json || !json.success || !json.method) throw new Error('Invalid response');

              replaceMethodRow(json.method);
              if (!editing && json.method && json.method.sort_order !== undefined) {
                const nextSort = (parseInt(json.method.sort_order, 10) || 0) + 1;
                defaultSortOrder = String(nextSort);
                resetForm(defaultSortOrder);
              } else {
                resetForm();
              }
            } catch (err) {
              console.error(err);
              // fallback não-AJAX
              form.submit();
            }
          });
        }

        document.querySelectorAll('.pm-row').forEach(wireRowInteractions);
        updateEmptyStates();
        refreshToggleAllState();
      })();
    </script>
  </section>
</div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
