<!doctype html>
<html lang="pt-br">
<?php
$companyData = is_array($company ?? null) ? $company : null;
$adminPrimaryColor = admin_theme_primary_color($companyData);
$adminPrimarySoft = hex_to_rgba($adminPrimaryColor, 0.55, $adminPrimaryColor);
$adminPrimaryGradient = admin_theme_gradient($companyData);
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
      .max-w-5xl {
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
  </style>
</head>
<body class="bg-slate-50 text-slate-900">
  <div class="max-w-5xl mx-auto p-4">
    <?= $content ?? '' ?>
  </div>
</body>
</html>
