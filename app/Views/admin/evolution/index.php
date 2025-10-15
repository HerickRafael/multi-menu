<?php
$title = 'Evolution - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

ob_start(); ?>

<div class="mx-auto max-w-4xl p-4">
  <h2 class="mb-4 text-lg font-semibold text-white">Evolution - Instâncias</h2>

  <div class="mb-6 rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-sm text-white">
    <div class="mb-4 text-sm text-slate-600">
      <div><strong>Server URL:</strong> <?= e($company['evolution_server_url'] ?? '—') ?></div>
      <div><strong>API Key:</strong> <?= $company['evolution_api_key'] ? str_repeat('*', 8) . substr($company['evolution_api_key'], -4) : '—' ?></div>
      <?php if (!empty($remote) && isset($remote) && is_array($remote)): ?>
        <!-- remote is present -> detected prefix likely saved -->
      <?php else: ?>
        <div class="mt-2 text-xs text-amber-600">Dica: se as instâncias não aparecerem, verifique se o "Server URL" contém o prefixo correto (ex.: <code>https://api.example.com/api/v2</code>). Você pode editar em Configurações.</div>
      <?php endif; ?>
      <?php if (file_exists(sys_get_temp_dir() . '/evolution_prefix_' . ($company['id'] ?? ''))): ?>
        <div class="mt-1 text-xs text-slate-500">Prefix detectado: <strong><?= e(trim(@file_get_contents(sys_get_temp_dir() . '/evolution_prefix_' . ($company['id'] ?? '')))) ?></strong></div>
      <?php endif; ?>
    </div>

    <form id="form-create" method="post" action="<?= e(base_url('admin/' . $slug . '/evolution/create')) ?>">
      <?php if (function_exists('csrf_field')): ?><?= csrf_field() ?><?php endif; ?>
      <div class="grid gap-2 md:grid-cols-3">
        <input name="label" placeholder="Rótulo (opcional)" class="rounded-xl border px-3 py-2">
        <input name="number" placeholder="Número/ID da instância" class="rounded-xl border px-3 py-2">
        <div><button id="btn-create" class="rounded-xl bg-emerald-600 px-4 py-2 text-white">Conectar</button></div>
      </div>
    </form>
  </div>
  <div class="mb-4 flex justify-between items-center">
    <div>
      <button id="btn-sync" class="rounded-xl border border-slate-700 px-3 py-1 text-sm">Sincronizar</button>
    </div>
    <div>
      <button id="btn-new" class="rounded-xl bg-emerald-600 px-3 py-1 text-white">Instance +</button>
    </div>
  </div>

  <div id="instances" class="grid gap-4">
    <?php foreach ($instances as $inst): ?>
      <div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-sm text-white" data-id="<?= e($inst['id']) ?>">
        <div class="flex items-start gap-4">
          <div class="flex-1">
            <div class="flex items-center justify-between">
              <div>
                <strong class="text-lg"><?= e($inst['label'] ?: $inst['number']) ?></strong>
                <div class="text-xs text-slate-400">@<?= e($inst['instance_identifier']) ?> • <?= e($inst['status']) ?></div>
              </div>
              <div class="flex gap-2">
                <a href="<?= base_url('admin/' . $slug . '/evolution/instance/' . rawurlencode($inst['instance_identifier'])) ?>" class="inline-block rounded-xl border border-blue-500 px-3 py-1 text-sm text-blue-400 hover:bg-blue-500/10">Configurar</a>
                <button class="btn-refresh rounded-xl border px-3 py-1 text-sm" data-id="<?= e($inst['id']) ?>">Atualizar QR</button>
                <button class="btn-delete rounded-xl border px-3 py-1 text-sm text-red-400" data-id="<?= e($inst['id']) ?>">Delete</button>
              </div>
            </div>

            <?php if (!empty($inst['qr_code'])): ?>
              <div class="mt-3">
                <img src="data:image/png;base64,<?= e($inst['qr_code']) ?>" alt="QR code" class="h-48">
              </div>
            <?php else: ?>
              <div class="mt-3 text-sm text-slate-400">QR code não disponível.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($remote) && is_array($remote)): ?>
    <h3 class="mt-6 mb-2 text-base font-semibold">Instâncias remotas disponíveis</h3>
    <div class="grid gap-4">
      <?php foreach ($remote as $r): ?>
        <div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-sm text-white">
          <div class="flex items-center justify-between">
            <div>
              <strong><?= e($r['label'] ?? $r['number'] ?? $r['instance_identifier'] ?? '') ?></strong>
              <div class="text-xs text-slate-500">ID: <?= e($r['instance_identifier'] ?? ($r['id'] ?? '')) ?> • Status: <?= e($r['status'] ?? '') ?></div>
            </div>
            <div>
              <button class="btn-import rounded-xl border px-3 py-1 text-sm" data-id="<?= e($r['instance_identifier'] ?? ($r['id'] ?? '')) ?>">Importar</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
(()=>{
  const slug = '<?= $slug ?>';
  const base = '<?= e(base_url('admin/' . $slug . '/evolution')) ?>';

  async function postJson(url, body){
    const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json', 'Accept':'application/json'}, body: JSON.stringify(body||{})});
    return res.json();
  }

  // create
  document.getElementById('form-create').addEventListener('submit', async (ev)=>{
    ev.preventDefault();
    const form = ev.target;
    const fd = new FormData(form);
    const body = Object.fromEntries(fd.entries());
    const json = await postJson(base + '/create', body);
    if (json.error){ alert('Erro: '+json.error); return; }
    // append new card
    const inst = json.instance;
    const container = document.getElementById('instances');
    const div = document.createElement('div'); div.className='rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-sm text-white';
    div.innerHTML = `<div class="flex items-start gap-4"><div class="flex-1"><div class="flex items-center justify-between"><div><strong class="text-lg">${inst.label||inst.number}</strong><div class="text-xs text-slate-400">@${inst.instance_identifier} • ${inst.status}</div></div><div class="flex gap-2"><a href="${base}/instance/${encodeURIComponent(inst.instance_identifier)}" class="inline-block rounded-xl border border-blue-500 px-3 py-1 text-sm text-blue-400 hover:bg-blue-500/10">Configurar</a><button class="btn-refresh rounded-xl border px-3 py-1 text-sm" data-id="">Atualizar QR</button><button class="btn-delete rounded-xl border px-3 py-1 text-sm text-red-400" data-id="">Delete</button></div></div>${inst.qr?`<div class="mt-3"><img src="data:image/png;base64,${inst.qr}" class="h-48"></div>`:'<div class="mt-3 text-sm text-slate-400">QR code não disponível.</div>'}</div></div>`;
    container.prepend(div);
  });

  // sync
  document.getElementById('btn-sync').addEventListener('click', async ()=>{
    const res = await postJson(base + '/sync', {});
    if (res.error) return alert('Erro: '+res.error);
    alert('Sincronizado: importadas='+res.imported+' puladas='+res.skipped);
    location.reload();
  });

  // delegated handlers
  document.getElementById('instances').addEventListener('click', async (ev)=>{
    const t = ev.target;
    if (t.classList.contains('btn-refresh')){
      const id = t.dataset.id;
      const res = await postJson(base + '/refresh', {id});
      if (res.error) return alert('Erro: '+res.error);
      alert('QR atualizado'); location.reload();
    }
    if (t.classList.contains('btn-delete')){
      if (!confirm('Remover instância?')) return;
      const id = t.dataset.id;
      const res = await postJson(base + '/delete', {id});
      if (res.error) return alert('Erro: '+res.error);
      alert('Removido'); location.reload();
    }
  });

  // import remote
  document.querySelectorAll('.btn-import').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id = b.dataset.id;
      const res = await postJson(base + '/import', {instance_identifier: id});
      if (res.error) return alert('Erro: '+res.error);
      alert('Importado: '+res.instance_identifier); location.reload();
    });
  });

})();
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php';
