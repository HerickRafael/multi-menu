<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/models/Company.php';

$slug = $argv[1] ?? 'wollburger';
$number = $argv[2] ?? '551920017687';

$company = Company::findBySlug($slug);
if (!$company) { echo "Company not found\n"; exit(1); }

$server = rtrim($company['evolution_server_url'] ?? '', '/');
$key = $company['evolution_api_key'] ?? '';

$paths = [
    '/contact/exists',
    '/contact/check',
    '/contacts/exists',
    '/contact/validate',
    '/contacts/validate',
    '/contact/exist',
    '/contact/isExists',
    '/contact/existsNumber',
    '/contact/exists?number=' . rawurlencode($number),
    '/contact/check?number=' . rawurlencode($number),
];

$headersVariants = [
    ['Authentication-Api-Key: ' . $key, 'Accept: application/json'],
    ['apikey: ' . $key, 'Accept: application/json'],
    ['Authorization: Bearer ' . $key, 'Accept: application/json'],
];

foreach ($paths as $p) {
    echo "--- TRY {$p} ---\n";
    foreach ($headersVariants as $h) {
        $url = $server . '/' . ltrim($p, '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "header=" . implode(',', $h) . " -> HTTP {$code} ";
        if ($err) echo "ERR: {$err}\n";
        else echo "body: " . substr($resp ?? '', 0, 600) . (strlen($resp ?? '') > 600 ? '... (truncated)' : '') . "\n";
    }
    echo "\n";
}

exit(0);
