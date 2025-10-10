<?php
// admin/settings/index.php — Configurações (com toolbar fixa)

$title = 'Configurações - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$days  = [1 => 'Segunda',2 => 'Terça',3 => 'Quarta',4 => 'Quinta',5 => 'Sexta',6 => 'Sábado',7 => 'Domingo'];

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Normalização de cores (se ainda não existir)
if (!function_exists('settings_color_value')) {
    function settings_color_value($value, $default)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return strtoupper($default);
        }

        if ($value[0] !== '#') {
            $value = '#'.$value;
        }

        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return strtoupper($default);
        }

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

<div class="mx-auto max-w-6xl p-4">

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<form id="settingsForm" method="post" enctype="multipart/form-data"
      action="<?= e(base_url('admin/' . $slug . '/settings')) ?>"
      class="relative grid max-w-5xl gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>

  <!-- TOOLBAR FIXA -->
  <div class="sticky top-0 z-20 -m-4 mb-0 border-b bg-white/85 px-4 py-2 backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto flex max-w-5xl items-center justify-between">
      <div class="flex items-center gap-2 text-sm text-slate-800">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
          <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <strong>Configurações</strong>
      </div>
      <div class="flex gap-2">
        <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-1.5 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Salvar
        </button>
      </div>
    </div>
  </div>

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
        <?php $logoPreview = !empty($company['logo']) ? e(base_url($company['logo'])) : ''; ?>
        <span class="mb-1 block text-sm text-slate-700">Logo (quadrado) – jpg/png/webp</span>
        <div class="mb-2">
          <div id="site-logo-dropzone" class="inline-block rounded-xl border-2 border-dashed bg-white p-3 relative admin-primary-border" style="min-height:84px; display:flex; align-items:center; justify-content:center;">
            <input id="site-logo-input" type="file" name="logo" accept=".svg,.png,.jpg,.jpeg,.webp" class="sr-only">
            <div id="site-logo-drop-hint" class="flex flex-col items-center justify-center text-center py-3">
              <div class="text-slate-600 mb-2">Arraste o logo ou</div>
              <button type="button" id="site-logo-choose" class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-sm admin-primary-text admin-primary-border">anexar arquivo</button>
            </div>
            <div class="dz-preview-frame" style="aspect-ratio:1/1; width:100%; max-width:180px;">
              <img id="site-logo-preview" src="<?= $logoPreview ?>" alt="preview" class="rounded-md <?= $logoPreview === '' ? 'hidden' : '' ?>" style="width:100%; height:100%; object-fit:contain; display:block;" />
            </div>
            <button type="button" id="site-logo-clear" class="absolute top-2 right-2 <?= $logoPreview === '' ? 'hidden' : '' ?> rounded-full bg-white text-slate-700 shadow-sm px-2 py-0.5 border admin-primary-border">✕</button>
          </div>
        </div>
        <small class="text-xs text-slate-500">Recomendado: 512×512px. Máx. 5 MB.</small>
      </div>

      <div>
        <?php $bannerPreview = !empty($company['banner']) ? e(base_url($company['banner'])) : ''; ?>
        <span class="mb-1 block text-sm text-slate-700">Banner (largura) – jpg/png/webp</span>
        <div class="mb-2">
          <div id="site-banner-dropzone" class="rounded-xl border-2 border-dashed bg-white p-3 relative admin-primary-border" style="min-height:96px; display:flex; align-items:center; justify-content:center;">
            <input id="site-banner-input" type="file" name="banner" accept=".jpg,.jpeg,.png,.webp" class="sr-only">
            <div id="site-banner-drop-hint" class="flex flex-col items-center justify-center text-center py-4">
              <div class="text-slate-600 mb-2">Arraste o banner ou</div>
              <button type="button" id="site-banner-choose" class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-sm admin-primary-text admin-primary-border">anexar arquivo</button>
            </div>
            <div class="dz-preview-frame" style="aspect-ratio:4/1; width:100%;">
              <img id="site-banner-preview" src="<?= $bannerPreview ?>" alt="preview" class="rounded-md <?= $bannerPreview === '' ? 'hidden' : '' ?>" style="width:100%; height:100%; object-fit:cover; display:block;" />
            </div>
            <button type="button" id="site-banner-clear" class="absolute top-2 right-2 <?= $bannerPreview === '' ? 'hidden' : '' ?> rounded-full bg-white text-slate-700 shadow-sm px-2 py-0.5 border admin-primary-border">✕</button>
          </div>
        </div>
        <small class="text-xs text-slate-500">Recomendado: 1600×400px. Máx. 5 MB.</small>
      </div>
    </div>
    <script>
      (function(){
        function wireDropzoneLocal(opts){
          const dz = document.getElementById(opts.dropzone);
          const input = document.getElementById(opts.input);
          const preview = document.getElementById(opts.preview);
          const choose = document.getElementById(opts.choose);
          const clearBtn = document.getElementById(opts.clear);
          const hint = dz ? dz.querySelector('[id$="-drop-hint"]') : null;
          if (!dz || !input) return;
          function showPreviewFile(file){
            try{
              const url = URL.createObjectURL(file);
              if (preview) { preview.src = url; preview.classList.remove('hidden'); }
              if (hint) hint.classList.add('hidden');
              if (clearBtn) clearBtn.classList.remove('hidden');
              if (preview) preview.onload = ()=>{ try{ URL.revokeObjectURL(url);}catch(_){} };
            }catch(_){}
          }
          function clearSelection(){ try{ input.value = ''; }catch(_){} if (preview){ preview.removeAttribute('src'); preview.classList.add('hidden'); } if (hint) hint.classList.remove('hidden'); if (clearBtn) clearBtn.classList.add('hidden'); }
          if (choose) choose.addEventListener('click', e=>{ e.preventDefault(); input.click(); });
          if (clearBtn) clearBtn.addEventListener('click', e=>{ e.preventDefault(); clearSelection(); });
          input.addEventListener('change', function(){ if (this.files && this.files.length>0) { showPreviewFile(this.files[0]); } else { clearSelection(); } });
          dz.addEventListener('dragover', function(e){ e.preventDefault(); dz.classList.add('opacity-80'); });
          dz.addEventListener('dragleave', function(e){ dz.classList.remove('opacity-80'); });
          dz.addEventListener('drop', function(e){ e.preventDefault(); dz.classList.remove('opacity-80'); const dt = e.dataTransfer; if (dt && dt.files && dt.files.length>0) { input.files = dt.files; input.dispatchEvent(new Event('change',{bubbles:true})); } });
        }
        wireDropzoneLocal({ dropzone:'site-logo-dropzone', input:'site-logo-input', preview:'site-logo-preview', choose:'site-logo-choose', clear:'site-logo-clear' });
        wireDropzoneLocal({ dropzone:'site-banner-dropzone', input:'site-banner-input', preview:'site-banner-preview', choose:'site-banner-choose', clear:'site-banner-clear' });
      })();
    </script>
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
          $row    = $hours[$d] ?? ['is_open' => 0,'open1' => null,'close1' => null,'open2' => null,'close2' => null];
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
              <input name="open1[<?= $d ?>]" value="<?= e(substr((string)$row['open1'], 0, 5)) ?>" placeholder="18:00"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                     data-day="<?= $d ?>">
            </label>
            <label class="grid gap-1">
              <span class="text-xs text-slate-600">Fecha 1</span>
              <input name="close1[<?= $d ?>]" value="<?= e(substr((string)$row['close1'], 0, 5)) ?>" placeholder="23:59"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                     data-day="<?= $d ?>">
            </label>

            <label class="slot2 grid gap-1" data-day="<?= $d ?>" style="<?= ($row['open2'] || $row['close2']) ? '' : 'display:none' ?>">
              <span class="text-xs text-slate-600">Abre 2</span>
              <input name="open2[<?= $d ?>]" value="<?= e(substr((string)$row['open2'], 0, 5)) ?>" placeholder="11:30"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
            </label>
            <label class="slot2 grid gap-1" data-day="<?= $d ?>" style="<?= ($row['open2'] || $row['close2']) ? '' : 'display:none' ?>">
              <span class="text-xs text-slate-600">Fecha 2</span>
              <input name="close2[<?= $d ?>]" value="<?= e(substr((string)$row['close2'], 0, 5)) ?>" placeholder="14:00"
                     class="time-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
            </label>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  </div>

  </form>

  <?php
  $content = ob_get_clean();
include __DIR__ . '/../layout.php';
