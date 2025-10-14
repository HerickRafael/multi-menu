<?php
$title = 'Gerente de Evolução';
$slug  = rawurlencode((string)($company['slug'] ?? ''));
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gerente de Evolução</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --bg: #0b1113;            /* fundo geral escuro (ligeiro tom esverdeado) */
      --card: #0f1c1f;          /* card base */
      --card-alt: #0f2526;      /* campos internos */
      --stroke: #1f2c30;        /* bordas sutis */
      --txt: #e6f0f2;           /* texto principal */
      --muted: #9fb3b9;         /* texto secundário */
      --accent: #16a34a;        /* verde */
      --accent-pressed: #12833d;
      --danger: #ef4444;
      --danger-pressed: #dc2626;
    }
    html, body { background: var(--bg); color: var(--txt); }
    /* scrollbar sutil */
    ::-webkit-scrollbar { height: 8px; width: 10px; }
    ::-webkit-scrollbar-thumb { background: #1c2a2e; border-radius: 999px; }
    ::-webkit-scrollbar-track { background: transparent; }
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
    .card { background: var(--card); border: 1px solid var(--stroke); }
    .inner { background: var(--card-alt); border: 1px solid var(--stroke); }
    .chip { background: rgba(22,163,74,.1); color: #9ae6b4; border: 1px solid rgba(22,163,74,.25); }
    .btn-green { background: var(--accent); }
    .btn-green:hover { background: var(--accent-pressed); }
    .btn-red { background: var(--danger); }
    .btn-red:hover { background: var(--danger-pressed); }
    .btn-ghost { background: transparent; border: 1px solid var(--stroke); }
    .muted { color: var(--muted); }
    .token { letter-spacing: 1.5px; }
    .ring-soft { box-shadow: 0 0 0 6px rgba(22,163,74,.08); }
  </style>
</head>
<body class="min-h-screen">

  <!-- Topbar -->
  <header class="sticky top-0 z-30 border-b border-[color:var(--stroke)]/60 bg-[color:var(--bg)]/80 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <!-- logo circle -->
          <div class="h-8 w-8 rounded-full bg-emerald-500 grid place-items-center ring-1 ring-emerald-400/40 ring-offset-2 ring-offset-[color:var(--bg)]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#062b16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M8 12h8"/>
              <path d="M12 8v8"/>
            </svg>
          </div>
          <span class="text-lg font-semibold">Gerente de Evolução</span>
        </div>
        <div class="flex items-center gap-2">
          <button class="btn-ghost rounded-lg px-3 py-2 text-sm hover:bg-white/5" title="Idioma">
            <!-- globe -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <circle cx="12" cy="12" r="9"/>
              <path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>
            </svg>
          </button>
          <button class="btn-ghost rounded-lg px-3 py-2 text-sm hover:bg-white/5" title="Tema">
            <!-- moon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
            </svg>
          </button>
          <button class="btn-ghost rounded-lg px-3 py-2 text-sm hover:bg-white/5" title="Sair">
            <!-- power -->
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
              <path d="M12 2v10"/>
              <path d="M7 5.3A9 9 0 1 0 17 5.3"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Título e toolbar -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Instâncias</h1>
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <!-- Search -->
      <div class="relative w-full md:max-w-2xl">
        <input id="search" type="text" placeholder="Procurar" class="w-full rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card)] px-4 py-3 pr-10 text-[15px] placeholder:muted focus:outline-none focus:ring-2 focus:ring-emerald-600/50" />
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[color:var(--muted)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
          <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3"/>
        </svg>
      </div>
      <!-- Right actions -->
      <div class="flex items-center gap-2 self-start md:self-auto">
        <button id="refreshBtn" class="btn-ghost rounded-xl px-3 py-2 text-sm hover:bg-white/5 flex items-center gap-2" title="Atualizar">
          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v6h-6"/></svg>
        </button>
        <button id="newInstanceBtn" class="btn-green rounded-xl px-4 py-2 text-sm font-medium text-white flex items-center gap-2">
          <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Instância +
        </button>
        <div class="relative">
          <button id="statusBtn" class="btn-ghost rounded-xl px-3 py-2 text-sm hover:bg-white/5 flex items-center gap-2">
            Status
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <!-- dropdown -->
          <div id="statusMenu" class="hidden absolute right-0 mt-2 w-40 rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card)] p-1 shadow-lg">
            <button data-status="all" class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/5">Todos</button>
            <button data-status="connected" class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/5">Conectado</button>
            <button data-status="disconnected" class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/5">Desconectado</button>
            <button data-status="error" class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/5">Erro</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cards -->
    <section id="cards" class="grid gap-5 md:grid-cols-2">
      <!-- Template via JS -->
    </section>
  </main>

  <!-- Modal New Instance -->
  <div id="modalNewInstance" class="fixed inset-0 z-40 hidden">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="relative z-10 mx-auto max-w-3xl px-4 py-10">
      <div id="modalCard" class="card rounded-2xl p-6 md:p-8">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold">Nova instância</h2>
          <button id="modalClose" class="btn-ghost rounded-lg p-2 hover:bg-white/5" title="Fechar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m18 6-12 12M6 6l12 12"/></svg>
          </button>
        </div>
        <form id="formNewInstance" method="post" action="<?= e(base_url('admin/' . $slug . '/evolution/create')) ?>">
          <div class="space-y-4">
            <label class="block text-sm">
              <span class="mb-1 inline-flex items-center gap-1">Nome <span class="text-red-500">*</span></span>
              <input id="fName" name="label" type="text" class="w-full rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card-alt)] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-600/50" />
            </label>
            <label class="block text-sm">
              <span class="mb-1 inline-flex items-center">Channel</span>
              <select id="fChannel" class="w-full rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card-alt)] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-600/50">
                <option>Baileys</option>
              </select>
            </label>
            <label class="block text-sm">
              <span class="mb-1 inline-flex items-center gap-1">Token <span class="text-red-500">*</span></span>
              <input id="fToken" name="token" type="text" value="14BAB6BE56EE-4C6F-AAD4-84387B2191EA" class="w-full rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card-alt)] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-600/50" />
            </label>
            <label class="block text-sm">
              <span class="mb-1 inline-flex items-center">Número</span>
              <input id="fNumber" name="number" type="text" placeholder="ex.: 5551999999999" class="w-full rounded-xl border border-[color:var(--stroke)] bg-[color:var(--card-alt)] px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-600/50" />
            </label>
          </div>
          <div class="mt-6 flex justify-end">
            <button id="modalSave" type="submit" class="btn-green rounded-xl px-5 py-2.5 font-medium text-white">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div id="toast" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 grid place-items-center opacity-0 transition-opacity"></div>

  <script>
    // Dados das instâncias vindos do PHP
    const instances = <?= json_encode(array_map(function($inst) {
      // Gerar avatar baseado no nome/label
      $name = $inst['profile_name'] ?? $inst['label'] ?: ($inst['api_number'] ?? $inst['number'] ?: 'Instance');
      $letters = strtoupper(substr($name, 0, 2));
      $colors = ['bg-amber-400', 'bg-sky-400', 'bg-emerald-400', 'bg-purple-400', 'bg-pink-400', 'bg-indigo-400'];
      $color = $colors[abs(crc32($name)) % count($colors)];
      
      // Determinar número de telefone da instância
      $instancePhone = '';
      if (isset($inst['api_number']) && $inst['api_number']) {
        $instancePhone = $inst['api_number'];
      } elseif (isset($inst['number']) && $inst['number']) {
        $instancePhone = $inst['number'];
      } else {
        // Gerar número baseado no nome da empresa (placeholder)
        $baseNumber = '5511'; // Código do Brasil + SP
        $hash = abs(crc32($inst['label'] ?? 'default'));
        $lastDigits = str_pad((string)($hash % 100000000), 8, '0', STR_PAD_LEFT);
        
        // Formatar como número brasileiro: +55 (11) 9 1234-5678
        $instancePhone = '+55 (11) 9 ' . substr($lastDigits, 0, 4) . '-' . substr($lastDigits, 4, 4);
      }
      
      // Determinar status correto baseado na API
      $status = 'disconnected'; // Status padrão
      if (isset($inst['connection_status'])) {
        $apiStatus = strtolower($inst['connection_status']);
        if (in_array($apiStatus, ['open', 'connected', 'ready'])) {
          $status = 'connected';
        } elseif (in_array($apiStatus, ['connecting', 'qr_code', 'qr'])) {
          $status = 'pending';
        } else {
          $status = 'disconnected';
        }
      } else {
        // Fallback para status do banco local
        $localStatus = strtolower($inst['status'] ?: 'pending');
        if (in_array($localStatus, ['connected', 'open', 'ready'])) {
          $status = 'connected';
        } elseif (in_array($localStatus, ['pending', 'qr_code', 'qr'])) {
          $status = 'pending';
        } else {
          $status = 'disconnected';
        }
      }
      
      return [
        'id' => $inst['id'],
        'instance_name' => $inst['label'] ?? $inst['name'] ?? 'Instance',
        'contact_name' => $name,
        'phone' => $inst['api_number'] ?? $inst['number'] ?: '',
        'instance_phone' => $instancePhone,
        'handle' => ($inst['api_number'] ?? $inst['number']) ? '@' . ($inst['api_number'] ?? $inst['number']) : '',
        'token' => $inst['token'] ?? $inst['instance_identifier'] ?? str_repeat('*', 36),
        'users' => (string)($inst['chat_count'] ?? 0), // Número de conversas
        'messages' => (string)($inst['message_count'] ?? 0), // Número de mensagens
        'status' => $status,
        'avatar' => ['letters' => $letters, 'color' => $color],
        'profile_pic_url' => $inst['profile_pic_url'] ?? null,
        'instance_identifier' => $inst['instance_identifier']
      ];
    }, $instances)) ?>;

    const cardsEl = document.getElementById('cards');

    function iconSettings() {
      return `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .66.39 1.26 1 1.51.23.1.48.15.73.15H21a2 2 0 1 1 0 4h-.09c-.25 0-.5.05-.73.15-.61.25-1 .85-1 1.51Z"/></svg>`
    }
    function iconCopy(){
      return `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`
    }
    function iconEye(){
      return `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`
    }
    function iconUser(){ return `<svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>` }
    function iconChat(){ return `<svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>` }
    function iconCheck(){ return `<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20 6-11 11-5-5"/></svg>` }

    function getStatusChip(status) {
      if (status === 'connected') {
        return `<span class="chip inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium">${iconCheck()} Connected</span>`;
      } else if (status === 'pending') {
        return `<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium bg-yellow-400/10 text-yellow-400 border border-yellow-400/25">⏳ Pending</span>`;
      } else {
        return `<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium bg-red-400/10 text-red-400 border border-red-400/25">❌ Disconnected</span>`;
      }
    }

    function renderCard(data){
      const card = document.createElement('article');
      card.className = 'card rounded-2xl p-4 md:p-5 flex flex-col gap-4';
      card.dataset.name = (data.instance_name + ' ' + data.contact_name + ' ' + data.handle + ' ' + data.phone).toLowerCase();
      card.dataset.status = data.status;

      card.innerHTML = `
        <div class="flex items-center justify-between">
          <h3 class="text-base md:text-[17px] font-semibold">${data.instance_name}</h3>
          <button class="btn-ghost rounded-lg p-2 hover:bg-white/5" title="Configurações">${iconSettings()}</button>
        </div>

        <div class="inner token flex items-center gap-2 rounded-xl px-3 py-2 text-sm">
          <span class="grow select-all overflow-hidden text-ellipsis whitespace-nowrap" data-token="${data.token}">${data.token}</span>
          <button class="rounded-lg p-1.5 hover:bg-white/5" title="Copiar" data-action="copy">${iconCopy()}</button>
          <button class="rounded-lg p-1.5 hover:bg-white/5" title="Mostrar" data-action="reveal">${iconEye()}</button>
        </div>

        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            ${data.profile_pic_url ? 
              `<div class="h-9 w-9 rounded-full overflow-hidden bg-gray-200">
                <img src="${data.profile_pic_url}" alt="Profile" class="h-full w-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="h-full w-full grid place-items-center rounded-full text-xs font-bold text-black ${data.avatar.color}" style="display:none">${data.avatar.letters}</div>
              </div>` : 
              `<div class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-black ${data.avatar.color}">${data.avatar.letters}</div>`
            }
            <div>
              <div class="font-medium leading-tight">${data.contact_name || 'Contato'}</div>
              <div class="muted text-xs leading-tight">${data.instance_phone || data.phone || data.handle || '&nbsp;'}</div>
            </div>
          </div>
          <div class="flex items-center gap-6 text-sm">
            <div class="flex items-center gap-1.5 muted"><span>${iconUser()}</span><span class="text-[13px] text-white/90">${data.users}</span></div>
            <div class="flex items-center gap-1.5 muted"><span>${iconChat()}</span><span class="text-[13px] text-white/90">${data.messages}</span></div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-1">
          ${getStatusChip(data.status)}
          <button class="btn-red rounded-xl px-3 py-2 text-sm font-medium text-white" data-action="delete">Delete</button>
        </div>
      `;

      // actions
      card.querySelector('[data-action="copy"]').addEventListener('click', () => {
        const real = card.querySelector('[data-token]').dataset.token;
        navigator.clipboard?.writeText(real);
        toast('Token copiado.');
      });
      card.querySelector('[data-action="reveal"]').addEventListener('click', () => {
        const span = card.querySelector('[data-token]');
        if (span.dataset.visible === '1') {
          span.textContent = span.dataset.token.replace(/./g, '*');
          span.dataset.visible = '0';
        } else {
          span.textContent = span.dataset.token;
          span.dataset.visible = '1';
        }
      });
      card.querySelector('[data-action="delete"]').addEventListener('click', () => {
        if (confirm(`Excluir a instância "${data.name}"?`)) {
          // Enviar requisição para deletar no servidor
          fetch(`<?= e(base_url('admin/' . $slug . '/evolution/delete')) ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${data.id}`
          }).then(() => {
            card.remove();
            toast('Instância excluída.');
          }).catch(() => {
            toast('Erro ao excluir instância.');
          });
        }
      });

      // inicia mascarado
      const tokenSpan = card.querySelector('[data-token]');
      tokenSpan.textContent = tokenSpan.dataset.token.replace(/./g, '*');
      tokenSpan.dataset.visible = '0';

      return card;
    }

    function mount(){
      cardsEl.innerHTML = '';
      instances.forEach(x => cardsEl.appendChild(renderCard(x)));
    }

    function toast(msg){
      const box = document.getElementById('toast');
      box.innerHTML = `<div class="rounded-xl bg-white/10 px-4 py-2 text-sm shadow-xl ring-1 ring-white/10">${msg}</div>`;
      box.classList.remove('opacity-0');
      box.classList.add('opacity-100');
      setTimeout(() => { box.classList.remove('opacity-100'); box.classList.add('opacity-0'); }, 1800);
    }

    mount();

    // New Instance Modal
    const btnNew = document.getElementById('newInstanceBtn');
    const modal = document.getElementById('modalNewInstance');
    const modalClose = document.getElementById('modalClose');
    const fName = document.getElementById('fName');

    function openNewInstance(){
      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      setTimeout(()=>fName.focus(), 50);
    }
    function closeNewInstance(){
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    btnNew.addEventListener('click', openNewInstance);
    modalClose.addEventListener('click', closeNewInstance);
    modal.addEventListener('click', (e)=>{ if(e.target === modal) closeNewInstance(); });
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && !modal.classList.contains('hidden')) closeNewInstance(); });

    // Search filter
    document.getElementById('search').addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      document.querySelectorAll('#cards > article').forEach(card => {
        const match = card.dataset.name.includes(q);
        card.style.display = match ? '' : 'none';
      });
    });

    // Status dropdown
    const statusBtn = document.getElementById('statusBtn');
    const statusMenu = document.getElementById('statusMenu');
    statusBtn.addEventListener('click', () => statusMenu.classList.toggle('hidden'));
    statusMenu.querySelectorAll('button').forEach(btn => btn.addEventListener('click', () => {
      const s = btn.dataset.status;
      statusMenu.classList.add('hidden');
      document.querySelectorAll('#cards > article').forEach(card => {
        card.style.display = (s === 'all' || card.dataset.status === s) ? '' : 'none';
      });
    }));

    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
      if (!statusMenu.contains(e.target) && !statusBtn.contains(e.target)) {
        statusMenu.classList.add('hidden');
      }
    });

    // Refresh button
    document.getElementById('refreshBtn').addEventListener('click', async () => {
      const refreshBtn = document.getElementById('refreshBtn');
      const originalHtml = refreshBtn.innerHTML;
      
      // Mostrar loading
      refreshBtn.innerHTML = '<svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v6h-6"/></svg>';
      refreshBtn.disabled = true;
      
      try {
        const response = await fetch('<?= e(base_url('admin/' . $slug . '/evolution/instances/data')) ?>');
        const data = await response.json();
        
        if (data.instances) {
          // Atualizar dados das instâncias
          instances.length = 0;
          instances.push(...data.instances);
          mount();
          toast('Dados atualizados com sucesso!');
        }
      } catch (error) {
        console.error('Erro ao atualizar dados:', error);
        toast('Erro ao atualizar dados.');
      } finally {
        // Restaurar botão
        refreshBtn.innerHTML = originalHtml;
        refreshBtn.disabled = false;
      }
    });
  </script>
</body>
</html>
