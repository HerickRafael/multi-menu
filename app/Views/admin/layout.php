<!doctype html>
<html lang="pt-br">
<?php
$companyData = is_array($company ?? null) ? $company : null;
$activeSlugValue = $activeSlug ?? null;
$companySlug = $activeSlugValue ?? ($companyData['slug'] ?? null);
$adminPrimaryColor = admin_theme_primary_color($companyData);
$adminPrimarySoft = hex_to_rgba($adminPrimaryColor, 0.55, $adminPrimaryColor);
$adminPrimaryGradient = admin_theme_gradient($companyData);
$kdsDataUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds/data') : null;
$orderDetailBaseUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/orders/show?id=') : null;
$kdsPageUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds') : null;
$bellConfig = (string)(config('kds_bell_url') ?? '');
$resolvedBellUrl = '';

if ($bellConfig !== '') {
    if (preg_match('/^(data:|https?:\/\/|\/\/)/i', $bellConfig)) {
        $resolvedBellUrl = $bellConfig;
    } else {
        $resolvedBellUrl = base_url(ltrim($bellConfig, '/'));
    }
}
?>
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --admin-primary-color: <?= e($adminPrimaryColor) ?>;
      --admin-primary-soft: <?= e($adminPrimarySoft) ?>;
      --admin-primary-gradient: <?= e($adminPrimaryGradient) ?>;
    }
    .admin-gradient-bg {
      background-image: var(--admin-primary-gradient);
      background-color: var(--admin-primary-color);
    }
    .admin-gradient-text {
      background-image: var(--admin-primary-gradient);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    /* Utilitários de cor primária do admin */
    .admin-primary-text { color: var(--admin-primary-color); }
    .admin-primary-underline { text-decoration-color: var(--admin-primary-color); }
    .admin-primary-bg { background-color: var(--admin-primary-color); }
    .admin-primary-soft-bg { background-color: var(--admin-primary-soft); }
    .admin-primary-soft-badge { background-color: var(--admin-primary-soft); color: var(--admin-primary-color); }
    .admin-primary-border { border-color: var(--admin-primary-color); }
    .admin-print-only {
      display: none;
    }
    .admin-print-only .receipt {
      width: 320px;
      max-width: calc(100% - 24px);
      margin: 0 auto;
      font-family: 'Courier New', Courier, monospace;
      font-size: 12px;
      line-height: 1.35;
      color: #000;
    }
    .admin-print-only .receipt-header {
      text-align: center;
    }
    .admin-print-only .receipt-section {
      text-align: left;
      margin-top: 6px;
    }
    .admin-print-only .receipt-header h1 {
      margin: 0 0 2px;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .admin-print-only .receipt-header p {
      margin: 0;
      font-size: 11px;
    }
    .admin-print-only .receipt hr {
      border: 0;
      border-top: 1px dashed #000;
      margin: 6px 0;
    }
    .admin-print-only .receipt-row {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
    }
    .admin-print-only .receipt-label {
      font-weight: 600;
      font-size: 11px;
      text-align: left;
    }
    .admin-print-only .receipt-text {
      font-size: 11px;
      text-align: left;
    }
    .admin-print-only .receipt-pre {
      white-space: pre-line;
    }
    .admin-print-only .receipt-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
    }
    .admin-print-only .receipt-table td {
      padding: 0;
      vertical-align: top;
    }
    .admin-print-only .receipt-item-name {
      font-weight: 600;
      margin-bottom: 2px;
    }
    .admin-print-only .receipt-item-row td {
      font-size: 11px;
    }
    .admin-print-only .receipt-item-row td.qty {
      width: 24px;
    }
    .admin-print-only .receipt-item-row td.total {
      text-align: right;
      width: 80px;
    }
    .admin-print-only .receipt-item-row td.price {
      text-align: right;
      width: 80px;
    }
    .admin-print-only .receipt-note {
      font-size: 10px;
      margin-bottom: 4px;
    }
    .admin-print-only .receipt-total {
      display: flex;
      justify-content: space-between;
      font-weight: 700;
      font-size: 13px;
      margin-top: 4px;
    }
    .admin-print-only .receipt-footer {
      margin-top: 10px;
      text-align: center;
      font-size: 11px;
    }
    @media print {
      html, body {
        height: auto;
        background: #fff;
      }
      body {
        margin: 0;
        padding: 0;
      }
      @page {
        size: 58mm auto;
        margin: 2mm 3mm;
      }
      .admin-screen-only {
        display: none !important;
      }
      .admin-print-only {
        display: block !important;
      }
      .max-w-7xl {
        max-width: 100% !important;
      }
      .mx-auto {
        margin: 0 !important;
      }
      .p-4 {
        padding: 0 !important;
      }
      .admin-print-only .receipt {
        width: 100%;
        max-width: 58mm;
      }
    }

    .admin-order-toasts {
      position: fixed;
      top: 1rem;
      right: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      z-index: 9999;
      pointer-events: none;
    }
    .admin-order-toast {
      pointer-events: auto;
      min-width: 260px;
      max-width: 320px;
      background: #fff;
      color: #0f172a;
      border-radius: 1rem;
      box-shadow: 0 22px 45px -24px rgba(15,23,42,0.55);
      padding: 1rem 1.2rem;
      border: 1px solid rgba(15,23,42,0.08);
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
      opacity: 0;
      transform: translateY(-8px);
      transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .admin-order-toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    .admin-order-toast h3 {
      margin: 0;
      font-size: 1rem;
      color: #0f172a;
      font-weight: 700;
    }
    .admin-order-toast p {
      margin: 0;
      font-size: 0.85rem;
      line-height: 1.35;
      color: #475569;
    }
      .admin-order-toast.row {
        display:flex;align-items:center;gap:0.75rem;
      }
      .admin-order-toast .toast-icon{
        width:36px;height:36px;flex-shrink:0;border-radius:.6rem;display:grid;place-items:center;color:#fff;font-weight:700;
      }
      .admin-order-toast.success { background: linear-gradient(90deg,#059669,#10b981); color:#fff; border: none; }
      .admin-order-toast.success .toast-icon{ background: rgba(255,255,255,0.12); }
      .admin-order-toast.error { background: linear-gradient(90deg,#ef4444,#f97316); color:#fff; border: none; }
      .admin-order-toast.error .toast-icon{ background: rgba(255,255,255,0.12); }
      .admin-order-toast.info { background: #0f172a; color:#fff; border: none; }
      .admin-order-toast .toast-close { position: absolute; right: 0.6rem; top: 0.6rem; background:transparent;border:none;color:inherit;font-size:14px;cursor:pointer; }
    .admin-order-toast-footer {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 0.45rem;
    }
    .admin-order-toast-actions {
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }
    .admin-order-toast-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.74rem;
      font-weight: 600;
      border-radius: 0.75rem;
      padding: 0.45rem 0.9rem;
      text-decoration: none;
      transition: background 0.15s ease, color 0.15s ease;
      border: none;
      cursor: pointer;
    }
    .admin-order-toast-btn.primary {
      background: var(--admin-primary-gradient);
      color: #fff;
    }
    .admin-order-toast-btn.primary:hover {
      opacity: 0.92;
    }
    .admin-order-toast-btn.secondary {
      background: rgba(226,232,240,0.6);
      color: #1f2937;
    }
    .admin-order-toast-btn.secondary:hover {
      background: rgba(226,232,240,0.9);
    }

  </style>
</head>
<body class="bg-slate-50 text-slate-900"
      data-company-slug="<?= e($companySlug ?? '') ?>"
      data-kds-url="<?= e($kdsDataUrl ?? '') ?>"
      data-order-url="<?= e($orderDetailBaseUrl ?? '') ?>"
      data-kds-link="<?= e($kdsPageUrl ?? '') ?>"
      data-bell-url="<?= e($resolvedBellUrl) ?>">
  <div class="max-w-7xl mx-auto p-4">
    <?= $content ?? '' ?>
  </div>
  <div class="admin-order-toasts" id="admin-order-toasts" aria-live="polite"></div>

  <script>
  // Função global de toast para todo o admin (implementação rica com ícone/fechar/timeout)
  (function(){
    const toastContainer = document.getElementById('admin-order-toasts');
    window.showToast = function(titleOrMessage, type){
      try {
        if (!toastContainer) return;
        const el = document.createElement('div');
        el.className = 'admin-order-toast ' + (type || 'info');
        el.style.position = 'relative';
        const icon = document.createElement('div');
        icon.className = 'toast-icon';
        icon.textContent = (type === 'success') ? '✓' : (type === 'error' ? '⚠' : 'i');
        const h = document.createElement('h3');
        const p = document.createElement('p');
        if (typeof titleOrMessage === 'string') {
          h.textContent = (type === 'error') ? 'Erro' : ((type === 'success') ? 'Sucesso' : 'Aviso');
          p.textContent = titleOrMessage;
        } else if (titleOrMessage && titleOrMessage.title) {
          h.textContent = String(titleOrMessage.title || '');
          p.textContent = String(titleOrMessage.message || '');
        } else {
          h.textContent = '';
          p.textContent = '';
        }
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => { el.classList.remove('show'); setTimeout(()=>{ try{ toastContainer.removeChild(el); }catch{} }, 180); });
        el.appendChild(icon);
        el.appendChild(h);
        el.appendChild(p);
        el.appendChild(closeBtn);
        toastContainer.appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => { el.classList.remove('show'); try{ toastContainer.removeChild(el); } catch(_){} }, 4200);
      } catch (e) { console.error(e); }
    };

  })();
  (function(){
    const dataUrl = document.body.dataset.kdsUrl;
    const orderUrlBase = document.body.dataset.orderUrl || '';
    const kdsLink = document.body.dataset.kdsLink || '';
    const bellConfig = (document.body.dataset.bellUrl || '').trim();
    if (!dataUrl) return;

    const POLL_INTERVAL = 6000;
    const DEFAULT_BELL_URI = 'data:audio/wav;base64,' +
      'UklGRjQrAABXQVZFZm10IBAAAAABAAEAIlYAAESsAAACABAAZGF0YRArAAAAAIIPDB60KrE0YjtcPnA9rDhcMAYlXxdBCKH4demz2zbQtse7wpbBWMTWyqbU'+
      'LeGi7x3/pA5DHQ0qNTQaO0w+mD0KOeswvCUxGCMJg/lL6m3cytAayOrCjMEWxF/KA9Ro4MbuOf7GDXkcYym3M846OD69PWY5eDFxJgMZBApm+iHrKt1h0YLI'+
      'HMOFwdfD7Mlj06Tf7O1V/egMrRu3KDUzfzohPt89vzkCMiQn0xnlCkn7+Ovo3frR7MhRw4HBmsN7ycTS4t4S7XH8CAzgGgkosTItOgY+/j0UOoky1SeiGsUL'+
      'LfzR7KjeldJZyYnDgcFhww3JKNIh3jnsjvsoCxEaWScrMtk56T0ZPmc6DjODKHAbpQwR/artat8z08nJxMODwSvDociO0WPdYeuq+kgKQhmnJqExgTnIPTE+'+
      'tzqQMzApPByEDfT9he4t4NPTPMoCxInB+cI5yPfQptyL6sf5ZwlwGPMlFTEmOaQ9Rj4DOxA02ikGHWIO2P5g7/LgddSyykTEk8HJwtTHYtDr27Xp5fiFCJ4X'+
      'PSWHMMg4fD1YPk07jDSCKtAdPw+8/zzwueEa1SrLicSfwZ3CccfQzzLb4egC+KMHyhaFJPYvaDhSPWY+kzsGNSgrlx4cEJ8AGfGB4sHVpcvQxK/BdMISx0DP'+
      'etoO6CH3wQb1FcsjYy8EOCQ9cT7XO341zCtdH/gQgwH38UrjatYjzBvFwsFOwrXGss7F2TznP/beBSAVDyPNLp038zx5Phc88jVtLCEg0xFnAtXyFuQV16TM'+
      'acXYwSvCXMYozhLZa+Ze9fsESBRRIjUuNDe/PH4+VDxkNg0t5CCtEksDtfPi5MLXJ826xfLBC8IFxp/NYNic5X70FwRwE5Ihmi3INog8fz6OPNM2qS2lIYYT'+
      'LgSU9LDlctitzQ7GDsLvwbHFGs2x187knvM0A5cS0SD9LFk2Tjx+PsU8PzdELmQiXhQRBXX1gOYj2TXOZMYuwtbBYcWXzATXAeS/8lACvREOIF0s5zURPHk+'+
      '+DyoN9wuIiM1FfUFVvZR59fZwM6+xlHCwMETxRbMWdY24+HxbAHiEEkfvCtyNdA7cD4pPQ44ci/dIwsW1wY39yPojNpOzxvHeMKtwcnEmcuw1W3iA/GIAAYQ'+
      'gx4YK/o0jDtlPlY9cTgFMJck4Ba6Bxn49uhE297Pe8ehwp7BgsQeywnVpeEm8KX/KQ+7HXIqgDRGO1Y+gD3SOJUwTyWzF5wI+/jL6f3bcdDex87CksE9xKbK'+
      'ZdTe4Ervwf5MDvIcySkDNPw6RD6nPS85JDEFJoUYfQne+aDqudwG0UPI/sKJwfzDMcrD0xngb+7d/W0NJxwfKYMzrzovPss9ijmvMbkmVhleCsH6d+t23Z3R'+
      'rMgxw4PBvsO+ySPTVt+U7fr8jwxbG3IoATNfOhY+7D3hOTgyaycmGj8LpPtP7DXeN9IYyWfDgcGDw07JhdKV3rvsFvyvC44awyd8Mgw6+z0JPjY6vzIbKPUa'+
      'HwyI/Cjt9d7U0obJoMOBwUvD4cjq0dXd4+sz+88KvhkSJ/QxtjncPSM+hzpCM8gowRv+DGz9Ae6333LT98ndw4XBF8N3yFHRF90L61D67gnuGF8majFdObo9'+
      'Oj7WOsMzdCmNHN0NT/7c7nzgFNRryhzEjcHlwhDIu9Bb3DXqbfkNCRwYqiXdMAE5lD1OPiE7QjQeKlcduw4z/7jvQeG31OLKX8SXwbfCrMcn0KDbYOmK+CsI'+
      'SRfzJE0wojhsPV4+aTu9NMUqIB6YDxYAlPAI4lzVW8ulxKXBjMJLx5bP6NqM6Kj3SQd1FjskvC9AOEA9az6vOzY1aivmHnQQ+gBy8dHiBNbXy+7EtsFkwuzG'+
      'B88x2rrnxvZmBqAVgCMnL9s3ET11PvE7rTUNLKwfUBHeAVDynOOu1lbMOsXKwT/CkcZ6zn3Z6Obl9YMFyhTDIpAudDffPHw+MDwgNq0scCAqEsICL/Nn5FrX'+
      '2MyJxeLBHsI5xvHNytgY5gT1oATyEwUi9y0JN6o8fz5sPJA2TC0yIQQTpgMO9DXlCNhczdvF/cEAwuPFas0a2EnlJPS8AxoTRSFbLZw2cTx/PqQ8/jboLfIh'+
      '3BOJBO70A+a52OPNMMYbwuTBkcXlzGvXfORF89kCQBKDIL0sKzY2PHw+2jxpN4EusCK0FGwFz/XT5mvZbc6IxjzCzcFCxWPMv9aw42by9QFmEb8fHSy4Nfc7'+
      'dj4MPdE3GC9tI4sVTwaw9qXnH9r5zuPGYMK4wfXE5MsV1uXiiPERAYoQ+h56K0I1tTtsPjs9NjitLygkYBYyB5H3d+jW2ofPQceIwqfBrMRny23VHOKq8C0A'+
      'rg80HtYqyjRwO18+aD2YOD8w4SQ0FxQIdPhL6Y7bGNCix7PCmcFmxO7Kx9RV4c7vSv/RDmsdLipONCg7Tz6QPfg4zzCYJQcY9ghW+SDqSNys0AbI4cKOwSPE'+
      'd8ok1I/g8u5m/vMNoRyFKdAz3To8PrY9VDlcMU0m2RjXCTn69uoE3ULRbcgSw4bB48MCyoLTy98X7oL9FA3WG9ooTzOPOiU+2D2tOeYxACeqGbgKHPvN68Ld'+
      '29HXyEbDgsGmw5HJ5NII3z3tn/w1DAkbLCjMMj46DD74PQM6bjKxJ3kamAv/+6Xsgd520kPJfcOBwWzDIslH0kjeZOy7+1ULOxp8J0Yy6jnvPRQ+Vzr0MmAo'+
      'Rxt4DOP8f+1D3xPTs8m4w4PBNsO3yK3Rid2M69j6dQprGcsmvTGTOc49LT6nOnYzDSkTHFcNx/1Z7gbgs9MlyvbDiMEDw07IFdHL3Lbq9fmUCZoYFyYyMTg5'+
      'qz1CPvQ69jO4Kd4cNg6r/jTvyuBV1JrKN8SRwdLC6MeA0BDc4OkS+bIIyBdhJaQw2ziEPVQ+Pjt0NGEqpx0TD4//EPCR4fnUEst7xJzBpcKFx+3PVtsL6TD4'+
      '0Af1FqokEzB7OFs9ZD6FO+40BytvHvAPcQDt8Fnin9WMy8LErMF8wiXHXM+f2jjoTvfuBiAW8COALxg4Lj1vPsk7ZjWrKzYfzBBVAcrxIuNI1grMDMW+wVXC'+
      'yMbOzunZZuds9gsGShU1I+susjf9PHg+CjzbNU0s+h+nETkCqfLt4/PWisxZxdPBMsJtxkPONdmV5ov1KAV0FHciUy5JN8o8fT5IPE027Sy9IIESHQOI87nk'+
      'oNcMzanF7MERwhbGus2E2MXlq/RFBJwTuCG5Ld42lDx/PoM8vTaKLX8hWxMBBGj0h+VP2JLN/cUIwvTBwsU0zdTX9+TL82EDwxL4IBwtbzZaPH4+ujwpNyUu'+
      'PiIzFOQESPVW5gDZGs5TxijC28FxxbHMJtcq5OzyfgLpETUgfiz+NR08ej7uPJM3vi78IgoVxwUp9ifns9mkzqzGSsLEwSPFMMx71l/jDfKaAQ4RcR/cK4k1'+
      '3TtyPh89+jdUL7gj4BWqBgr3+edo2jHPCMdwwrHB2MSyy9LVleIv8bYAMhCrHjkrEjWaO2c+TT1eOOgvciS1FowH7PfM6B/bwc9ox5jCocGQxDbLKtXM4VLw'+
      '0/9WD+QdkyqZNFQ7WT54Pb84eTAqJYkXbwjO+KDp2NtT0MrHxcKUwUvEvsqG1Abhdu/v/ngOGx3rKRw0CztIPqA9HTkHMeElWxhQCbH5deqT3OjQL8j0worB'+
      'CcRIyuPTQeCa7gv+mg1QHEEpnTO+OjM+xD14OZMxlSYtGTEKlPpM61Ddf9GXyCbDhMHKw9XJQ9N938DtJ/27DIQblSgbM286HD7lPdA5HTJHJ/0ZEgt3+yTs'+
      'Dt4Y0gLJXMOBwY/DZMml0rve5uxE/NwLtxrmJ5YyHToAPgM+JTqkMvgnyxryC1r8/OzO3rTScMmUw4HBVsP3yAnS+90O7GD7/AroGTYnDzLHOeI9Hj53Oigz'+
      'piiZG9EMPv3W7ZDfU9PgydDDhMEhw4zIcNE93TbrffobChgZgyaGMW85wT02PsY6qjNSKWQcsA0i/rDuVODz01PKD8SLwe/CJcjZ0IDcYOqa+ToJRhjPJfkw'+
      'FDmcPUo+EjspNPwpLx2ODgb/jO8a4ZbUyspRxJXBwMLAx0TQxduL6bf4WAh0FxglajC1OHQ9Wz5bO6U0pCr4HWwP6v9o8ODhO9VDy5fEosGUwl7Hs88N27fo'+
      '1fd2B6AWYCTZL1Q4ST1pPqE7HjVJK78eSBDNAEXxqeLi1b7L38SywWzC/8Yjz1ba5Ofz9pMGyxWlI0Uv8DcbPXM+5DuVNewrhB8kEbEBI/Jz44zWPcwqxcbB'+
      'RsKjxpbOodkS5xL2sAX1FOkiry6JN+k8ez4jPAk2jixJIP8RlAIC8z/kONe+zHnF3cEkwkrGDM7u2ELmMfXNBB0UKyIWLh83tTx/PmA8ejYsLQsh2BJ4A+Hz'+
      'C+Xl10HNysX3wQXC9MWEzT3YcuVR9OoDRRNrIXstsjZ9PH8+mTzoNsktyyGxE1wEwfTa5ZXYyM0fxhTC6sGhxf/Mjtel5HHzBgNsEqog3SxCNkI8fT7PPFQ3'+
      'Yy6KIokUPwWi9armR9lRznbGNcLRwVHFfczh1tnjk/IjApER5x89LM81BDx3PgI9vTf6LkcjYBUiBoP2e+f72dzO0cZZwrzBBMX9yzfWDuO08T8BthAiH5sr'+
      'WjXDO24+Mj0iOI8vAyQ1FgUHZPdN6LHaa88ux4DCqsG6xIDLjtVF4tfwWwDaD1se9yriNH47Yj5fPYU4IjC8JAoX5wdG+CDpadv7z4/HqsKbwXTEBsvo1H3h'+
      '+u94//0Okx1QKmc0NztTPog95TiyMHQl3RfJCCn59ekj3I7Q8sfXwpDBMMSOykTUt+Ae75T+Hw7KHKcp6jPtOkA+rz1COUAxKSavGKoJC/rL6t7cJNFYyAjD'+
      'h8HvwxnKo9Py30PusP1BDf8b/ChpM586Kj7SPZw5yzHdJoAZiwrv+qLrnN280cHIO8OCwbLDp8kD0y/fae3M/GIMMhtPKOYyTzoRPvI98jlTMo4nUBpsC9L7'+
      'euxb3lfSLclyw4HBeMM4yWbSbt6Q7On7ggtkGqAnYTL7OfU9Dj5GOtkyPigeG0sMtfxT7Rzf89KcyazD';

    const ACTIVITY_TIMEOUT = 15000;
    let lastUserActivity = 0;
    let isFetching = false;
    let syncToken = null;
    let initialized = false;
    let knownPending = new Set();

    const toastContainer = document.getElementById('admin-order-toasts');

    const prepareUri = (value) => {
      if (!value) return '';
      const raw = String(value).trim();
      if (!raw) return '';
      if (/^(data:|https?:|\/\/)/i.test(raw)) return raw;
      if (raw.startsWith('/')) {
        return window.location.origin + raw;
      }
      try {
        return new URL(raw, window.location.href).toString();
      } catch {
        return raw;
      }
    };

    const isPageActive = () => {
      if (document.hidden) return false;
      if (!lastUserActivity) return false;
      return (Date.now() - lastUserActivity) <= ACTIVITY_TIMEOUT;
    };

    class KdsChime {
      constructor(fallbackUri){
        this.AudioContext = window.AudioContext || window.webkitAudioContext || null;
        this.fallbackUri = prepareUri((typeof fallbackUri === 'string' && fallbackUri.trim()) ? fallbackUri.trim() : DEFAULT_BELL_URI);
        this.preferFallback = this.fallbackUri && this.fallbackUri !== DEFAULT_BELL_URI;

        this.context = null;
        this.unlocked = false;
        this.pendingRing = false;
        this.pendingMode = null;
        this.lastPlayedAt = 0;
        this.minimumGapMs = 450;
        this.loopInterval = null;
        this.shortTimeout = null;
        this.isAlarmRunning = false;

        this.audioEl = null;
        this.audioFailed = false;
      }

      isActivated(){
        return this.unlocked;
      }

      activate(){
        if (this.unlocked) return;
        this.unlocked = true;
        this.ensureContext();
        this.warmFallbackAudio();
        if (this.pendingRing) {
          const mode = this.pendingMode || 'loop';
          this.pendingRing = false;
          this.pendingMode = null;
          if (mode === 'short') {
            this.playShortAlert();
          } else {
            this.startPersistentAlert();
          }
        }
      }

      ring(mode){
        const chosenMode = mode || (isPageActive() ? 'short' : 'loop');
        if (!this.unlocked) {
          this.pendingRing = true;
          this.pendingMode = chosenMode;
          return;
        }
        const now = Date.now();
        if (this.isAlarmRunning && (now - this.lastPlayedAt) < this.minimumGapMs) {
          return;
        }
        if (chosenMode === 'short') {
          this.playShortAlert();
        } else {
          this.startPersistentAlert();
        }
      }

      playShortAlert(){
        this.stopAlarm(false);
        this.pendingRing = false;
        this.pendingMode = null;
        this.isAlarmRunning = true;
        this.playOnce();
        this.shortTimeout = setTimeout(() => {
          this.playOnce();
          this.shortTimeout = setTimeout(() => this.stopAlarm(), 900);
        }, 800);
      }

      startPersistentAlert(){
        this.stopAlarm(false);
        this.pendingRing = false;
        this.pendingMode = null;
        this.isAlarmRunning = true;
        const playNow = () => this.playOnce();
        playNow();
        this.loopInterval = setInterval(playNow, 4000);
      }

      handleUserActivity(){
        if (!this.unlocked) return;
        if (this.isAlarmRunning) {
          this.stopAlarm();
        }
      }

      stopAlarm(resetPending = true){
        if (this.loopInterval) {
          clearInterval(this.loopInterval);
          this.loopInterval = null;
        }
        if (this.shortTimeout) {
          clearTimeout(this.shortTimeout);
          this.shortTimeout = null;
        }
        this.isAlarmRunning = false;
        if (resetPending) {
          this.pendingRing = false;
          this.pendingMode = null;
        }
      }

      playOnce(){
        let played = false;
        const markPlayed = () => {
          this.lastPlayedAt = Date.now();
        };

        if (this.preferFallback && !this.audioFailed) {
          played = this.playFallback(markPlayed);
          if (!played && this.AudioContext) {
            this.ensureContext();
            if (this.context && this.playWithContext()) {
              markPlayed();
              played = true;
            }
          }
        } else {
          this.ensureContext();
          if (this.context && this.playWithContext()) {
            markPlayed();
            played = true;
          }
          if (!played) {
            played = this.playFallback(markPlayed);
          }
        }

        if (played) {
          this.pendingRing = false;
        }
        return played;
      }

      ensureContext(){
        if (this.context || !this.AudioContext) return;
        try {
          this.context = new this.AudioContext();
          if (this.context && this.context.state === 'suspended') {
            this.context.resume().catch(() => {});
          }
        } catch (err) {
          this.context = null;
          this.AudioContext = null;
        }
      }

      playWithContext(){
        if (!this.context) return false;
        try {
          const ctx = this.context;
          if (ctx.state === 'suspended') ctx.resume().catch(() => {});
          const now = ctx.currentTime;
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          osc.type = 'triangle';
          osc.frequency.setValueAtTime(880, now);
          gain.gain.setValueAtTime(0.0001, now);
          gain.gain.exponentialRampToValueAtTime(0.32, now + 0.02);
          gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.7);
          osc.connect(gain);
          gain.connect(ctx.destination);
          osc.start(now);
          osc.stop(now + 0.72);
          return true;
        } catch (err) {
          this.context = null;
          return false;
        }
      }

      playFallback(onSuccess){
        const fallbackToTone = () => {
          this.audioFailed = true;
          this.preferFallback = false;
          const ok = this.fallbackToTone();
          if (ok && typeof onSuccess === 'function') {
            onSuccess();
          }
          return ok;
        };
        if (!this.fallbackUri) return fallbackToTone();
        try {
          if (!this.audioEl) {
            this.audioEl = new Audio();
            this.audioEl.preload = 'auto';
            this.audioEl.src = this.fallbackUri;
            this.audioEl.volume = 0.8;
          }
          this.audioEl.muted = false;
          this.audioEl.onerror = () => {
            if (!fallbackToTone()) {
              this.pendingRing = true;
            }
          };
          try { this.audioEl.currentTime = 0; } catch {}
          const p = this.audioEl.play();
          if (p && typeof p.then === 'function') {
            p.then(() => {
              this.audioFailed = false;
              if (typeof onSuccess === 'function') {
                onSuccess();
              }
            }).catch(() => {
              if (!fallbackToTone()) {
                this.pendingRing = true;
              }
            });
            return true;
          }
          this.audioFailed = false;
          if (typeof onSuccess === 'function') {
            onSuccess();
          }
          return true;
        } catch (err) {
          return fallbackToTone();
        }
      }

      warmFallbackAudio(){
        if (!this.fallbackUri) return;
        try {
          if (!this.audioEl) {
            this.audioEl = new Audio();
            this.audioEl.preload = 'auto';
            this.audioEl.src = this.fallbackUri;
            this.audioEl.volume = 0.8;
          }
          const el = this.audioEl;
          const prevMuted = el.muted;
          el.muted = true;
          const reset = () => {
            try {
              el.pause();
              el.currentTime = 0;
            } catch {}
            el.muted = prevMuted;
          };
          const playPromise = el.play();
          if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(() => reset()).catch(() => reset());
          } else {
            reset();
          }
        } catch {}
      }

      fallbackToTone(){
        if (!this.AudioContext) return false;
        if (this.audioEl) {
          try { this.audioEl.pause(); } catch {}
        }
        this.audioEl = null;
        this.ensureContext();
        if (!this.context) return false;
        return this.playWithContext();
      }

      dispose(){
        this.stopAlarm();
        if (this.context && typeof this.context.close === 'function') {
          try { this.context.close(); } catch {}
        }
        this.context = null;
        if (this.audioEl) {
          try {
            this.audioEl.pause();
            this.audioEl.currentTime = 0;
          } catch {}
        }
        this.audioEl = null;
      }
    }

    const chime = new KdsChime(prepareUri(bellConfig) || DEFAULT_BELL_URI);

    const ensureChimeActivated = () => {
      if (chime.isActivated()) return;
      chime.activate();
    };

    ensureChimeActivated();
    lastUserActivity = Date.now();

    const trackActivity = () => {
      ensureChimeActivated();
      lastUserActivity = Date.now();
      chime.handleUserActivity();
    };

    const activityEvents = ['pointerdown','touchstart','keydown','wheel','touchmove'];
    activityEvents.forEach(evt => {
      document.addEventListener(evt, trackActivity, {passive: true});
    });
    window.addEventListener('scroll', trackActivity, {passive: true});

const formatCurrency = (value) => {
      try {
        return new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'}).format(Number(value || 0));
      } catch (err) {
        const num = Number(value || 0).toFixed(2).replace('.', ',');
        return 'R$ ' + num;
      }
    };

    const showToast = (order) => {
      if (!toastContainer) return;
      const toast = document.createElement('article');
      toast.className = 'admin-order-toast';
      const total = formatCurrency(order.total || order.subtotal || 0);
      const name = order.customer_name || 'Cliente';
      const phone = order.customer_phone || '';
      const orderLink = orderUrlBase ? orderUrlBase + encodeURIComponent(order.id) : '';
      toast.innerHTML = `
        <h3>Novo pedido #${order.id}</h3>
        <p>${name}${phone ? ' · ' + phone : ''}</p>
        <div class="admin-order-toast-footer">
          <span class="text-sm font-semibold text-slate-500">${total}</span>
          <div class="admin-order-toast-actions">
            ${orderLink ? `<a class="admin-order-toast-btn secondary" href="${orderLink}" target="_blank" rel="noopener">Ver pedido</a>` : ''}
            ${kdsLink ? `<a class="admin-order-toast-btn primary" href="${kdsLink}" target="_blank" rel="noopener">Abrir KDS</a>` : ''}
            <button type="button" class="admin-order-toast-btn secondary" data-dismiss>Fechar</button>
          </div>
        </div>
      `;
      toast.querySelectorAll('[data-dismiss]').forEach(btn => {
        btn.addEventListener('click', () => {
          chime.stopAlarm();
          toast.remove();
        });
      });
      toastContainer.appendChild(toast);
      requestAnimationFrame(() => toast.classList.add('show'));
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 200);
      }, 15000);
    };

    const orderKey = (value) => {
      const num = Number(value);
      return Number.isFinite(num) ? Math.trunc(num) : 0;
    };

    const collectPending = (orders) => {
      const set = new Set();
      orders.forEach(order => {
        if (!order) return;
        const status = String(order.status || '').toLowerCase();
        if (status !== 'pending') return;
        const id = orderKey(order.id || order.order_id);
        if (id > 0) set.add(id);
      });
      return set;
    };

    const processData = (data) => {
      const orders = Array.isArray(data.orders) ? data.orders : [];
      const pendingSet = collectPending(orders);

      if (!initialized) {
        knownPending = pendingSet;
        initialized = true;
        return;
      }

      const newOrders = [];
      orders.forEach(order => {
        if (!order) return;
        const status = String(order.status || '').toLowerCase();
        if (status !== 'pending') return;
        const id = orderKey(order.id || order.order_id);
        if (id > 0 && !knownPending.has(id)) {
          newOrders.push(order);
        }
      });

      knownPending = pendingSet;

      if (!newOrders.length) return;

      newOrders.forEach(order => showToast(order));
      chime.ring();
    };

    const fetchData = () => {
      if (isFetching) return;
      isFetching = true;
      let url = dataUrl;
      if (syncToken) {
        url += (url.includes('?') ? '&' : '?') + 'since=' + encodeURIComponent(syncToken);
      }
      fetch(url, {credentials: 'include'})
        .then(res => res.ok ? res.json() : Promise.reject())
        .then(data => {
          syncToken = data.sync_token || data.server_time || syncToken;
          processData(data);
        })
        .catch(() => {})
        .finally(() => {
          isFetching = false;
        });
    };

    fetchData();
    setInterval(fetchData, POLL_INTERVAL);
    window.addEventListener('beforeunload', () => chime.dispose());
  })();
  </script>
  <script src="<?= base_url('assets/js/admin.js') ?>"></script>
</body>
</html>
