<?php
// admin/settings/index.php — Configurações (versão moderna)

$title = "Configurações - " . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$days  = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Normalização de cores (se ainda não existir)
if (!function_exists('settings_color_value')) {
  function settings_color_value($value, $default) {
    $value = trim((string)$value);
    if ($value === '') return strtoupper($default);
    if ($value[0] !== '#') $value = '#'.$value;
    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) return strtoupper($default);
    if (strlen($value) === 4) {
      $value = '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3];
    }
    return strtoupper($value);
  }
}

$colorDefaults = [
  'menu_header_text_color'       => '#FFFFFF',
  'menu_header_button_color'     => '#FACC15',
  'menu_header_bg_color'         => '#5B21B6',
  'menu_logo_border_color'       => '#7C3AED',
  'menu_group_title_bg_color'    => '#FACC15',
  'menu_group_title_text_color'  => '#000000',
  'menu_welcome_bg_color'        => '#6B21A8',
  'menu_welcome_text_color'      => '#FFFFFF',
];

$colorValues = [];
foreach ($colorDefaults as $key => $default) {
  $colorValues[$key] = settings_color_value($company[$key] ?? '', $default);
}

// Horários vindos do controller (pode estar vazio)
$hours = $hours ?? [];

ob_start(); ?>

<!-- HEADER -->
<header class="mb-6 flex items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-emerald-500 text-white shadow">
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
      <path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </span>
  <h1 class="bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-2xl font-semibold text-transparent">
    Configurações gerais
  </h1>

  <div class="ml-auto flex items-center gap-2">
    <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z" stroke="currentColor" stroke-width="1.6"/></svg>
      Categorias
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Produtos
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Dashboard
    </a>
  </div>
</header>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<form id="settingsForm" method="post" enctype="multipart/form-data"
      action="<?= e(base_url('admin/' . $slug . '/settings')) ?>"
      class="grid max-w-5xl gap-6">

  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>

  <!-- CARD: Dados principais -->
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h10M6 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Informações do comércio
    </legend>

    <div class="grid gap-3 md:grid-cols-2">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Nome do comércio</span>
        <input name="name" value="<?= e($company['name'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">WhatsApp</span>
        <input id="whats" name="whatsapp" value="<?= e($company['whatsapp'] ?? '') ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
               inputmode="numeric" placeholder="(51) 92001-7687">
        <small class="text-xs text-slate-500">Será mostrado no botão "Falar no WhatsApp".</small>
      </label>
    </div>

    <label class="mt-3 grid gap-1">
      <span class="text-sm text-slate-700">Endereço (opcional)</span>
      <input name="address" value="<?= e($company['address'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
    </label>

    <div class="mt-3 grid gap-3 md:grid-cols-3">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Pedido mínimo (R$)</span>
        <input name="min_order" type="number" step="0.01" value="<?= e($company['min_order'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Tempo médio (de) – min</span>
        <input name="avg_delivery_min_from" type="number" min="1" step="1"
               value="<?= e($company['avg_delivery_min_from'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="40">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Tempo médio (até) – min</span>
        <input name="avg_delivery_min_to" type="number" min="1" step="1"
               value="<?= e($company['avg_delivery_min_to'] ?? '') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="60">
      </label>
    </div>

    <label class="mt-3 grid gap-1">
      <span class="text-sm text-slate-700">Texto de destaque (boas-vindas)</span>
      <textarea name="highlight_text" rows="3" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" placeholder="Ex.: Peça online e retire sem fila!"><?= e($company['highlight_text'] ?? '') ?></textarea>
    </label>
  </fieldset>

  <!-- CARD: Aparência do cardápio -->
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Aparência do cardápio
    </legend>
    <p class="mb-3 text-sm text-slate-600">Personalize as cores exibidas no cardápio on-line.</p>

    <div class="grid gap-3 md:grid-cols-2">
      <?php
      $labels = [
        'menu_header_text_color'      => 'Texto do cabeçalho',
        'menu_header_button_color'    => 'Botões/ícones do cabeçalho',
        'menu_header_bg_color'        => 'Fundo do cabeçalho',
        'menu_logo_border_color'      => 'Borda da logo',
        'menu_group_title_bg_color'   => 'Fundo do título dos grupos',
        'menu_group_title_text_color' => 'Texto do título dos grupos',
        'menu_welcome_bg_color'       => 'Fundo da mensagem de boas-vindas',
        'menu_welcome_text_color'     => 'Texto da mensagem de boas-vindas',
      ];
      foreach ($labels as $key => $lab): ?>
        <label class="grid gap-1">
          <span class="text-sm text-slate-700"><?= e($lab) ?></span>
          <div class="flex items-center gap-3">
            <input type="color" name="<?= e($key) ?>" value="<?= e($colorValues[$key]) ?>" class="h-11 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white">
            <input type="text" value="<?= e($colorValues[$key]) ?>" data-color-for="<?= e($key) ?>"
                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-mono uppercase tracking-wide text-slate-800 focus:ring-2 focus:ring-indigo-400">
          </div>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <!-- CARD: Logo & Banner -->
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Identidade visual
    </legend>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <span class="mb-1 block text-sm text-slate-700">Logo (quadrado) – jpg/png/webp</span>
        <div class="mb-2 flex items-center gap-3">
          <img id="logo-preview"
               src="<?= !empty($company['logo']) ? e(base_url($company['logo'])) : e(base_url('assets/logo-placeholder.png')) ?>"
               class="h-20 w-20 rounded-xl border border-slate-200 object-cover ring-1 ring-slate-200"
               alt="Pré-visualização da logo">
          <input type="file" name="logo" id="logo-input" accept=".jpg,.jpeg,.png,.webp"
                 class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900">
        </div>
        <small class="text-xs text-slate-500">Recomendado: 512×512px. Máx. 5 MB.</small>
      </div>

      <div>
        <span class="mb-1 block text-sm text-slate-700">Banner (largura) – jpg/png/webp</span>
        <div class="mb-2">
          <img id="banner-preview"
               src="<?= !empty($company['banner']) ? e(base_url($company['banner'])) : e(base_url('assets/banner-placeholder.png')) ?>"
               class="h-24 w-full max-w-md rounded-xl border border-slate-200 object-cover ring-1 ring-slate-200"
               alt="Pré-visualização do banner">
        </div>
        <input type="file" name="banner" id="banner-input" accept=".jpg,.jpeg,.png,.webp"
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900">
        <small class="text-xs text-slate-500">Recomendado: 1600×400px. Máx. 5 MB.</small>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Horários -->
  <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z M12 8v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Horários de funcionamento
    </legend>
    <p class="mb-2 text-sm text-slate-600">Ative os dias e defina até dois intervalos por dia. Use HH:MM.</p>

    <div class="grid gap-2">
      <?php foreach ($days as $d => $label):
        $row    = $hours[$d] ?? ['is_open'=>0,'open1'=>null,'close1'=>null,'open2'=>null,'close2'=>null];
        $isOpen = !empty($row['is_open']); ?>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <div class="mb-2 flex items-center gap-3">
            <label class="inline-flex cursor-pointer items-center gap-2">
              <input type="checkbox" name="is_open[<?= $d ?>]" <?= $isOpen ? 'checked' : '' ?> data-day="<?= $d ?>" class="toggle-day h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
              <span class="font-medium text-slate-800"><?= e($label) ?></span>
            </label>
            <button type="button" class="ml-auto rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100 btn-slot2" data-day="<?= $d ?>">
              + Segundo horário
            </button>
          </div>

          <div class="grid items-end gap-2 md:grid-cols-4">
            <label class="grid gap-1">
              <span class="text-xs text-slate-600">Abre 1</span>
              <input name="open1[<?= $d ?>]" value="<?= e(substr((string)$row['open1'],0,5)) ?>" placeholder="18:00"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                     data-day="<?= $d ?>">
            </label>
            <label class="grid gap-1">
              <span class="text-xs text-slate-600">Fecha 1</span>
              <input name="close1[<?= $d ?>]" value="<?= e(substr((string)$row['close1'],0,5)) ?>" placeholder="23:59"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                     data-day="<?= $d ?>">
            </label>

            <label class="slot2 grid gap-1" data-day="<?= $d ?>" style="<?= ($row['open2']||$row['close2'])?'':'display:none' ?>">
              <span class="text-xs text-slate-600">Abre 2</span>
              <input name="open2[<?= $d ?>]" value="<?= e(substr((string)$row['open2'],0,5)) ?>" placeholder="11:30"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
            </label>
            <label class="slot2 grid gap-1" data-day="<?= $d ?>" style="<?= ($row['open2']||$row['close2'])?'':'display:none' ?>">
              <span class="text-xs text-slate-600">Fecha 2</span>
              <input name="close2[<?= $d ?>]" value="<?= e(substr((string)$row['close2'],0,5)) ?>" placeholder="14:00"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
            </label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <!-- AÇÕES -->
  <div class="flex gap-2">
    <button class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Salvar
    </button>
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Voltar
    </a>
  </div>
</form>

<!-- SCRIPTS -->
<script>
(function(){
  // ====== Helpers ======
  function digits(s){ return (s||'').replace(/\D+/g, ''); }
  function clamp(n, a, b){ return Math.max(a, Math.min(b, n)); }
  function validImage(file){ return /image\/(jpeg|png|webp)/.test(file.type) && file.size <= 5*1024*1024; }

  // ====== WhatsApp máscara + normalização ======
  const inputWhats = document.getElementById('whats');
  if (inputWhats){
    function toPretty(d){
      if (d.startsWith('55')) d = d.slice(2);
      d = d.slice(0, 13);
      const ddd = d.slice(0,2), rest = d.slice(2);
      if (rest.length >= 9) return `(${ddd}) ${rest.slice(0,5)}-${rest.slice(5)}`;
      if (rest.length >= 8) return `(${ddd}) ${rest.slice(0,4)}-${rest.slice(4)}`;
      if (rest.length > 0)  return `(${ddd}) ${rest}`;
      if (d.length >= 2)    return `(${ddd}) `;
      return d;
    }
    function onInput(){
      let d = digits(inputWhats.value);
      inputWhats.value = toPretty(d);
    }
    function beforeSubmit(){
      let d = digits(inputWhats.value).slice(0,15);
      if (d.length <= 11 && !d.startsWith('55')) d = '55' + d;
      inputWhats.value = d;
    }
    inputWhats.addEventListener('input', onInput);
    onInput();
    document.getElementById('settingsForm').addEventListener('submit', beforeSubmit);
  }

  // ====== Linkar inputs texto <-> color ======
  document.querySelectorAll('input[data-color-for]').forEach((txt)=>{
    const key = txt.getAttribute('data-color-for');
    const color = document.querySelector(`input[type="color"][name="${key}"]`);
    function norm(v){
      v = (v||'').trim().toUpperCase();
      if (!v) return '#000000';
      if (v[0] !== '#') v = '#'+v;
      if (!/^#([0-9A-F]{3}|[0-9A-F]{6})$/.test(v)) return color.value;
      if (v.length === 4) v = '#' + v[1]+v[1]+v[2]+v[2]+v[3]+v[3];
      return v;
    }
    if (color){
      // texto → color
      txt.addEventListener('change', ()=>{ color.value = norm(txt.value); txt.value = color.value; });
      // color → texto
      color.addEventListener('input', ()=>{ txt.value = color.value.toUpperCase(); });
    }
  });

  // ====== Preview de imagens (logo/banner) ======
  const logoInput = document.getElementById('logo-input');
  const logoPrev  = document.getElementById('logo-preview');
  const bannerInput = document.getElementById('banner-input');
  const bannerPrev  = document.getElementById('banner-preview');

  function previewFile(input, img){
    const f = input.files && input.files[0];
    if (!f) return;
    if (!validImage(f)) { alert('Formato inválido ou arquivo muito grande. Use JPG/PNG/WEBP até 5MB.'); input.value = ''; return; }
    const url = URL.createObjectURL(f);
    img.src = url;
    img.onload = ()=> URL.revokeObjectURL(url);
  }

  logoInput?.addEventListener('change', ()=>previewFile(logoInput, logoPrev));
  bannerInput?.addEventListener('change', ()=>previewFile(bannerInput, bannerPrev));

  // ====== Horários: habilitar/desabilitar e slot 2 ======
  document.querySelectorAll('.toggle-day').forEach(chk=>{
    const day = chk.dataset.day;
    function toggle(){
      const enabled = chk.checked;
      document.querySelectorAll('[data-day="'+day+'"].time-input').forEach(i=>{
        i.disabled = !enabled; i.classList.toggle('bg-gray-100', !enabled);
      });
      document.querySelectorAll('.slot2[data-day="'+day+'"] input').forEach(i=>{
        i.disabled = !enabled;
      });
    }
    chk.addEventListener('change', toggle); toggle();
  });

  document.querySelectorAll('.btn-slot2').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const day = btn.dataset.day;
      document.querySelectorAll('.slot2[data-day="'+day+'"]').forEach(el=>{
        el.style.display = (el.style.display==='none' || !el.style.display) ? 'block' : 'none';
      });
    });
  });

  // ====== Formatação HH:MM ======
  document.querySelectorAll('.time-input').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      let v = inp.value.replace(/\D+/g, '').slice(0,4);
      if (v.length >= 3) {
        let h = clamp(parseInt(v.slice(0,2)||'0',10), 0, 23).toString().padStart(2,'0');
        let m = clamp(parseInt(v.slice(2)||'0',10), 0, 59).toString().padStart(2,'0');
        inp.value = `${h}:${m}`;
      } else {
        inp.value = v;
      }
    });
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
