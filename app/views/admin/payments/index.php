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
$old     = is_array($old ?? null) ? $old : ['name' => '', 'instructions' => '', 'sort_order' => 0, 'active' => 1, 'type' => 'others', 'pix_key' => '', 'meta' => []];
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
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        const type = document.getElementById('pm-type');
        const pixFields = document.getElementById('pm-pix-fields');
        if (!type || !pixFields) return;
        function togglePix(){
          if (type.value === 'pix') {
            pixFields.classList.remove('hidden');
          } else {
            pixFields.classList.add('hidden');
          }
        }
        type.addEventListener('change', togglePix);
        togglePix();
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

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Nome da bandeira</span>
        <input id="pm-name" type="text" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Ex.: Visa, MasterCard, Pix, Dinheiro" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required autofocus aria-describedby="pm-name-help">
        <div id="pm-name-help" class="text-xs text-slate-400">Nome exibido ao cliente no checkout (ex.: Visa, Pix).</div>
      </label>

      <label class="grid gap-1 text-sm">
        <span class="font-semibold text-slate-700">Tipo</span>
        <?php $oldType = $old['type'] ?? 'others'; ?>
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

      <?php
      $oldMeta = is_array($old['meta'] ?? null) ? $old['meta'] : [];
      $oldPixKey = $old['pix_key'] ?? '';
      ?>
      <?php $pixFieldsClass = ($oldType === 'pix') ? 'grid gap-2' : 'hidden grid gap-2'; ?>
      <div id="pm-pix-fields" class="<?= $pixFieldsClass ?>">
        <h3 class="text-sm font-semibold">Credenciais Pix</h3>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Chave Pix</span>
          <input type="text" name="pix_key" value="<?= e($oldPixKey) ?>" placeholder="Ex.: 11999999999 ou chave aleatória" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
        </label>
        <label class="grid gap-1 text-sm">
          <span class="font-semibold text-slate-700">Provedor (opcional)</span>
          <input type="text" name="meta[px_provider]" value="<?= e($oldMeta['px_provider'] ?? '') ?>" placeholder="Ex.: Gerencianet, Pagar.me" class="rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
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

      <div class="flex gap-3">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Adicionar método
        </button>
        <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">Cancelar</a>
      </div>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-800">Métodos cadastrados</h2>
    <?php if (!$methods): ?>
      <p class="text-sm text-slate-500">Ainda não há métodos cadastrados. Utilize o formulário ao lado para iniciar.</p>
    <?php endif; ?>

    <!-- Tabs (exemplo visual) -->
    <div class="mt-3 flex items-center justify-between">
      <div class="flex gap-4 border-b">
        <button class="pb-2 text-sm font-medium border-b-2 border-rose-600 text-rose-600">Crédito</button>
        <button class="pb-2 text-sm text-slate-500">Débito</button>
        <button class="pb-2 text-sm text-slate-500">Outros</button>
        <button class="pb-2 text-sm text-slate-500">Vale-refeição</button>
      </div>
      <div class="flex items-center gap-3 text-sm text-slate-600">
        <span>Ativar todas</span>
        <label class="inline-flex items-center cursor-pointer">
          <input id="pm-toggle-all" type="checkbox" class="sr-only">
          <span class="w-10 h-6 bg-slate-200 rounded-full relative transition-colors">
            <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(0)"></span>
          </span>
        </label>
      </div>
    </div>

    <div class="mt-4">
      <div class="space-y-2" id="pm-list">
      <?php foreach ($methods as $method):
          $methodId = (int)($method['id'] ?? 0);
          $methodSlug = $base . '/' . $methodId;
          $isActive = !empty($method['active']);
          $pixKey = $method['pix_key'] ?? null;
          if (!$pixKey && !empty($method['type']) && $method['type'] === 'pix' && !empty($method['meta'])) {
              $m = is_string($method['meta']) ? json_decode($method['meta'], true) : (is_array($method['meta']) ? $method['meta'] : []);
              if (is_array($m) && !empty($m['px_key'])) {
                  $pixKey = $m['px_key'];
              }
          }
          ?>
        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200">
              <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M7 11h10M5 15h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <div class="font-semibold text-slate-800"><?= e($method['name'] ?? '') ?></div>
                <?php $type = $method['type'] ?? 'others'; ?>
                <div class="text-xs rounded-full px-2 py-1 <?= $type === 'pix' ? 'bg-emerald-100 text-emerald-700' : ($type === 'credit' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700') ?>"><?= e(ucfirst($type)) ?></div>
              </div>
              <?php if (!empty($method['type']) && $method['type'] === 'pix' && $pixKey): ?>
                <div class="text-xs text-slate-500">Chave Pix: <?= e($pixKey) ?></div>
              <?php endif; ?>
              <div class="text-xs text-slate-500">ID #<?= $methodId ?></div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="inline-flex items-center cursor-pointer">
              <input data-id="<?= $methodId ?>" type="checkbox" class="pm-toggle sr-only" <?= $isActive ? 'checked' : '' ?> />
              <span class="w-10 h-6 <?= $isActive ? 'bg-rose-500' : 'bg-slate-200' ?> rounded-full relative transition-colors">
                <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(<?= $isActive ? '20px' : '0' ?>)"></span>
              </span>
            </label>
            <a href="#" class="text-sm text-slate-500">Editar</a>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>

    <script>
      (function(){
        const base = '<?= $base ?>';
        const csrftoken = <?= function_exists('csrf_token') ? ('"' . addslashes(csrf_token()) . '"') : 'null' ?>;

        function escapeHtml(s){
          return (s + '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        // render a method row element
        function normalizeMeta(meta){
          if (!meta) return {};
          if (typeof meta === 'string') {
            try {
              const parsed = JSON.parse(meta);
              return parsed && typeof parsed === 'object' ? parsed : {};
            } catch(e) {
              return {};
            }
          }
          return meta;
        }

        function updateToggleAppearance(chk){
          if (!chk) return;
          const span = chk.parentElement ? chk.parentElement.querySelector('span.w-10') : null;
          if (!span) return;
          span.classList.toggle('bg-rose-500', chk.checked);
          span.classList.toggle('bg-slate-200', !chk.checked);
          const ball = span.querySelector('span.absolute');
          if (ball) ball.style.transform = 'translateX(' + (chk.checked ? '20px' : '0') + ')';
        }

        function renderMethodRow(method){
          const id = parseInt(method.id, 10);
          const isActive = parseInt(method.active, 10) === 1;
          const meta = normalizeMeta(method.meta);
          const pixKey = method.pix_key || (meta ? meta.px_key : null);
          const div = document.createElement('div');
          div.className = 'flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3';
          div.innerHTML = `
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-md bg-white flex items-center justify-center border border-slate-200">
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none"><path d="M3 7h18M7 11h10M5 15h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <div class="font-semibold text-slate-800">${escapeHtml(method.name || '')}</div>
                  <div class="text-xs rounded-full px-2 py-1 ${method.type === 'pix' ? 'bg-emerald-100 text-emerald-700' : (method.type === 'credit' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700')}">${escapeHtml((method.type || 'others').charAt(0).toUpperCase() + (method.type || 'others').slice(1))}</div>
                </div>
                ${method.type === 'pix' && pixKey ? `<div class="text-xs text-slate-500">Chave Pix: ${escapeHtml(pixKey)}</div>` : ''}
                <div class="text-xs text-slate-500">ID #${id}</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center cursor-pointer">
                <input data-id="${id}" type="checkbox" class="pm-toggle sr-only" ${isActive ? 'checked' : ''} />
                <span class="w-10 h-6 ${isActive ? 'bg-rose-500' : 'bg-slate-200'} rounded-full relative transition-colors">
                  <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transform transition-transform" style="transform: translateX(${isActive ? '20px' : '0'})"></span>
                </span>
              </label>
              <a href="#" class="text-sm text-slate-500">Editar</a>
            </div>
          `;

          const chk = div.querySelector('.pm-toggle');
          if (chk){
            chk.dataset.wired = '1';
            chk.addEventListener('change', function(){
              const on = this.checked;
              toggleMethod(id, on, function(response){
                const success = response && response.success;
                if (!success) {
                  chk.checked = !on;
                } else if (response && response.method) {
                  chk.checked = parseInt(response.method.active, 10) === 1;
                }
                updateToggleAppearance(chk);
              });
            });
            updateToggleAppearance(chk);
          }

          return div;
        }

        async function toggleMethod(id, on, cb){
          try{
            const url = base + '/' + id;
            const body = new URLSearchParams();
            body.append('active', on ? '1' : '0');
            if (csrftoken) body.append('csrf_token', csrftoken);
            const res = await fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Network');
            const json = await res.json().catch(()=>null);
            if (cb) cb(json);
            return json;
          } catch(e){
            console.error(e);
            alert('Erro ao atualizar o método. Atualize a página e tente novamente.');
            if (cb) cb(null);
            return null;
          }
        }

        // wire existing toggles
        function wireExistingToggles(){
          document.querySelectorAll('.pm-toggle').forEach(function(chk){
            if (chk.dataset.wired) return;
            chk.dataset.wired = '1';
            chk.addEventListener('change', function(){
              const id = this.dataset.id;
              const on = this.checked;
              toggleMethod(id, on, function(response){
                const success = response && response.success;
                if (!success) {
                  chk.checked = !on;
                } else if (response && response.method) {
                  chk.checked = parseInt(response.method.active, 10) === 1;
                }
                updateToggleAppearance(chk);
              });
            });
            updateToggleAppearance(chk);
          });
        }

        wireExistingToggles();

        const toggleAll = document.getElementById('pm-toggle-all');
        if (toggleAll){
          toggleAll.addEventListener('change', async function(){
            const on = this.checked ? '1' : '0';
            try{
              const url = base + '/batch';
              const body = new URLSearchParams();
              body.append('active', on);
              if (csrftoken) body.append('csrf_token', csrftoken);
              const res = await fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
              if (!res.ok) throw new Error('Network');
              const json = await res.json().catch(()=>null);
              if (!json || !json.success) throw new Error('Batch failed');

              // update UI without firing individual toggles
              document.querySelectorAll('.pm-toggle').forEach(function(chk){
                chk.checked = toggleAll.checked;
                updateToggleAppearance(chk);
              });
            }catch(e){
              console.error(e);
              alert('Erro ao atualizar todos os métodos. Atualize a página e tente novamente.');
              // revert toggleAll
              toggleAll.checked = !toggleAll.checked;
            }
          });
        }

        // Submit create form via AJAX and insert new method
        (function(){
          const form = document.getElementById('pm-create-form');
          const list = document.getElementById('pm-list');
          if (!form || !list || !window.fetch) return;

          form.addEventListener('submit', async function(e){
            // if user disabled JS or AJAX not supported, fall back to normal submit
            e.preventDefault();

            const data = new FormData(form);
            if (!data.has('active')) data.set('active', '0');
            try{
              const res = await fetch(form.action, { method: form.method || 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
              if (!res.ok) throw new Error('Network');
              const json = await res.json().catch(()=>null);
              if (!json || !json.success || !json.method) throw new Error('Invalid response');

              // insert new node at top
              const node = renderMethodRow(json.method);
              list.insertBefore(node, list.firstChild);
              // reset form and UI
              form.reset();
              const type = document.getElementById('pm-type');
              const pixFields = document.getElementById('pm-pix-fields');
              if (type && pixFields) pixFields.classList.add('hidden');
            }catch(err){
              console.error(err);
              // fallback: submit normally so server can render errors
              form.submit();
            }
          });
        })();
      })();
    </script>
  </section>
</div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
