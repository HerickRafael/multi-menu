<?php
// Backup evolution settings, set to mock, start mock server (background), run test script, restore settings.
require __DIR__ . '/../app/config/db.php';

$pdo = db();
$slug = 'wollburger';

// fetch current
$st = $pdo->prepare('SELECT id,evolution_server_url,evolution_api_key FROM companies WHERE slug = ?');
$st->execute([$slug]);
$company = $st->fetch(PDO::FETCH_ASSOC);
if (!$company) { echo "Company not found\n"; exit(1); }
$orig_server = $company['evolution_server_url'];
$orig_key = $company['evolution_api_key'];
$companyId = $company['id'];

echo "Backup: server={$orig_server} key={$orig_key}\n";

// update to mock
$mockUrl = 'http://127.0.0.1:8081';
$mockKey = 'testkey';
$up = $pdo->prepare('UPDATE companies SET evolution_server_url = ?, evolution_api_key = ? WHERE id = ?');
$up->execute([$mockUrl, $mockKey, $companyId]);

// start mock server in background using nohup
$cmd = "nohup php -S 127.0.0.1:8081 scripts/mock_evolution_router.php > /tmp/mock_evolution.out 2>&1 & echo $!";
$pid = null;
exec($cmd, $out);
if (isset($out[0])) { $pid = (int)trim($out[0]); }
echo "Started mock server, pid={$pid}\n";

// give it a moment
sleep(1);

// run test
passthru('php scripts/test_emit_order_notification.php wollburger', $ret);

// restore original
$up->execute([$orig_server, $orig_key, $companyId]);

// stop mock server if pid known
if ($pid) {
    exec('kill ' . (int)$pid);
    echo "Stopped mock server pid={$pid}\n";
}

exit($ret);
