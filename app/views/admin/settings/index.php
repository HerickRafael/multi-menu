<?php
$title = "Configurações - " . ($company['name'] ?? '');
$days = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];
$slug = rawurlencode((string)($company['slug'] ?? ''));

if (!function_exists('settings_color_value')) {
  function settings_color_value($value, $default) {
    $value = trim((string)$value);
    if ($value === '') {
      return strtoupper($default);
    }
    if ($value[0] !== '#') {
      $value = '#' . $value;
    }
    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
      return strtoupper($default);
    }
    if (strlen($value) === 4) {
      $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
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
ob_start(); ?>
<h1 class="text-2xl font-bold mb-4">Configurações gerais</h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>

<form id="settingsForm" method="post" enctype="multipart/form-data"
       action="<?= e(base_url('admin/' . $slug . '/settings')) ?>"
      class="grid gap-4 max-w-4xl bg-white p-4 rounded-2xl border">

  <div class="grid md:grid-cols-2 gap-3">
    <label class="grid gap-1">
      <span class="text-sm">Nome do comércio</span>
      <input name="name" value="<?= e($company['name']) ?>" class="border rounded-xl p-2">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">WhatsApp</span>
      <input id="whats" name="whatsapp" value="<?= e($company['whatsapp']) ?>"
             class="border rounded-xl p-2" inputmode="numeric" placeholder="(51) 92001-7687">
    </label>
  </div>

  <label class="grid gap-1">
    <span class="text-sm">Endereço (opcional)</span>
    <input name="address" value="<?= e($company['address']) ?>" class="border rounded-xl p-2">
  </label>

  <div class="grid md:grid-cols-3 gap-3">
    <label class="grid gap-1">
      <span class="text-sm">Pedido mínimo (R$)</span>
      <input name="min_order" type="number" step="0.01" value="<?= e($company['min_order']) ?>" class="border rounded-xl p-2">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Tempo médio (de) – min</span>
      <input name="avg_delivery_min_from" type="number" min="1" step="1"
             value="<?= e($company['avg_delivery_min_from']) ?>" class="border rounded-xl p-2" placeholder="40">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Tempo médio (até) – min</span>
      <input name="avg_delivery_min_to" type="number" min="1" step="1"
             value="<?= e($company['avg_delivery_min_to']) ?>" class="border rounded-xl p-2" placeholder="60">
    </label>
  </div>

  <label class="grid gap-1">
    <span class="text-sm">Texto de destaque (boas-vindas)</span>
    <textarea name="highlight_text" rows="3" class="border rounded-xl p-2"><?= e($company['highlight_text']) ?></textarea>
  </label>

  <hr class="my-2">

  <h2 class="text-lg font-semibold">Aparência do cardápio</h2>
  <p class="text-sm text-gray-600">Personalize as cores exibidas no cardápio on-line.</p>

  <div class="grid md:grid-cols-2 gap-3">
    <label class="grid gap-1">
      <span class="text-sm">Cor dos textos no cabeçalho do cardápio</span>
      <input type="color" name="menu_header_text_color" value="<?= e($colorValues['menu_header_text_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor dos botões e ícones do cabeçalho</span>
      <input type="color" name="menu_header_button_color" value="<?= e($colorValues['menu_header_button_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor de fundo do cabeçalho do cardápio</span>
      <input type="color" name="menu_header_bg_color" value="<?= e($colorValues['menu_header_bg_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor da borda da logo</span>
      <input type="color" name="menu_logo_border_color" value="<?= e($colorValues['menu_logo_border_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor de fundo do título dos grupos do cardápio</span>
      <input type="color" name="menu_group_title_bg_color" value="<?= e($colorValues['menu_group_title_bg_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor do título dos grupos do cardápio</span>
      <input type="color" name="menu_group_title_text_color" value="<?= e($colorValues['menu_group_title_text_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor de fundo da mensagem de boas-vindas</span>
      <input type="color" name="menu_welcome_bg_color" value="<?= e($colorValues['menu_welcome_bg_color']) ?>" class="border rounded-xl h-12">
    </label>

    <label class="grid gap-1">
      <span class="text-sm">Cor do texto da mensagem de boas-vindas</span>
      <input type="color" name="menu_welcome_text_color" value="<?= e($colorValues['menu_welcome_text_color']) ?>" class="border rounded-xl h-12">
    </label>
  </div>

  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <span class="text-sm block mb-1">Logo (quadrado) – jpg/png/webp</span>
      <?php if (!empty($company['logo'])): ?>
        <img src="<?= base_url($company['logo']) ?>" class="w-20 h-20 object-cover rounded-xl mb-2" alt="Logo atual">
      <?php endif; ?>
      <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="border rounded-xl p-2 w-full">
    </div>

    <div>
      <span class="text-sm block mb-1">Banner (largura) – jpg/png/webp</span>
      <?php if (!empty($company['banner'])): ?>
        <img src="<?= base_url($company['banner']) ?>" class="w-full max-w-md h-24 object-cover rounded-xl mb-2" alt="Banner atual">
      <?php endif; ?>
      <input type="file" name="banner" accept=".jpg,.jpeg,.png,.webp" class="border rounded-xl p-2 w-full">
    </div>
  </div>

  <hr class="my-2">

  <h2 class="text-lg font-semibold">Horários de funcionamento</h2>
  <p class="text-sm text-gray-600 mb-2">Ative os dias e defina até dois intervalos por dia. Use HH:MM.</p>

  <div class="grid gap-2">
    <?php foreach ($days as $d=>$label):
      $row = $hours[$d] ?? ['is_open'=>0,'open1'=>null,'close1'=>null,'open2'=>null,'close2'=>null];
      $isOpen = !empty($row['is_open']); ?>
      <div class="border rounded-2xl p-3 bg-slate-50">
        <div class="flex items-center gap-3 mb-2">
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_open[<?= $d ?>]" <?= $isOpen ? 'checked' : '' ?> data-day="<?= $d ?>" class="toggle-day">
            <span class="font-medium"><?= $label ?></span>
          </label>
          <button type="button" class="ml-auto px-3 py-1.5 rounded-xl border btn-slot2" data-day="<?= $d ?>">+ Segundo horário</button>
        </div>

        <div class="grid md:grid-cols-4 gap-2 items-end">
          <label class="grid gap-1">
            <span class="text-xs text-gray-600">Abre 1</span>
            <input name="open1[<?= $d ?>]" value="<?= e(substr((string)$row['open1'],0,5)) ?>" placeholder="18:00" class="border rounded-xl p-2 time-input" data-day="<?= $d ?>">
          </label>
          <label class="grid gap-1">
            <span class="text-xs text-gray-600">Fecha 1</span>
            <input name="close1[<?= $d ?>]" value="<?= e(substr((string)$row['close1'],0,5)) ?>" placeholder="23:59" class="border rounded-xl p-2 time-input" data-day="<?= $d ?>">
          </label>

          <label class="grid gap-1 slot2" data-day="<?= $d ?>" style="<?= ($row['open2']||$row['close2'])?'':'display:none' ?>">
            <span class="text-xs text-gray-600">Abre 2</span>
            <input name="open2[<?= $d ?>]" value="<?= e(substr((string)$row['open2'],0,5)) ?>" placeholder="11:30" class="border rounded-xl p-2 time-input">
          </label>
          <label class="grid gap-1 slot2" data-day="<?= $d ?>" style="<?= ($row['open2']||$row['close2'])?'':'display:none' ?>">
            <span class="text-xs text-gray-600">Fecha 2</span>
            <input name="close2[<?= $d ?>]" value="<?= e(substr((string)$row['close2'],0,5)) ?>" placeholder="14:00" class="border rounded-xl p-2 time-input">
          </label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="flex gap-2 mt-3">
    <button class="px-4 py-2 rounded-xl border">Salvar</button>
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="px-4 py-2 rounded-xl border">Voltar</a>
  </div>
</form>

<div class="mt-6 flex gap-2">
  <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/categories')) ?>">Categorias</a>
  <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Produtos</a>
</div>

<script>
(function(){
  // ===== Máscara de WhatsApp no próprio campo =====
  const input = document.getElementById('whats');
  function digits(s){ return (s||'').replace(/\D+/g, ''); }
  function toPretty(d){ // mostra (51) 92001-7687
    if (d.startsWith('55')) d = d.slice(2);
    d = d.slice(0, 13); // DDD + até 11 dígitos
    const ddd = d.slice(0,2);
    const rest = d.slice(2);
    if (rest.length >= 9) return `(${ddd}) ${rest.slice(0,5)}-${rest.slice(5)}`;
    if (rest.length >= 8) return `(${ddd}) ${rest.slice(0,4)}-${rest.slice(4)}`;
    if (rest.length > 0)  return `(${ddd}) ${rest}`;
    if (d.length >= 2)    return `(${ddd}) `;
    return d;
  }
  function onInput(){
    let d = digits(input.value);
    input.value = toPretty(d);
  }
  function beforeSubmit(){
    let d = digits(input.value).slice(0,15);
    if (d.length <= 11 && !d.startsWith('55')) d = '55'+d;
    input.value = d; // envia normalizado para o backend
  }
  input.addEventListener('input', onInput);
  onInput();
  document.getElementById('settingsForm').addEventListener('submit', beforeSubmit);

  // ===== Horários: habilitar/desabilitar e slot 2 =====
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

  // ===== Formatação dos horários (HH:MM) =====
  document.querySelectorAll('.time-input').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      let v = inp.value.replace(/\D+/g, '').slice(0,4);
      if (v.length >= 3) {
        inp.value = v.slice(0,2) + ':' + v.slice(2);
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
