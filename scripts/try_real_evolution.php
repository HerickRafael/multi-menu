<?php
declare(strict_types=1);
// Teste de conexão à Evolution usando credenciais guardadas em companies (por slug)
require_once __DIR__ . '/../app/models/Company.php';
require_once __DIR__ . '/../app/controllers/AdminEvolutionController.php';

$slug = $argv[1] ?? 'demo';
$company = Company::findBySlug($slug);
if (!$company) {
    echo "Empresa com slug '{$slug}' não encontrada. Verifique o slug nas settings.\n";
    exit(1);
}

echo "Usando company={$company['id']} slug={$slug}\n";

$controller = new AdminEvolutionController();
$ref = new ReflectionClass($controller);
if (!$ref->hasMethod('evolutionApiRequest')) {
    echo "Controller não possui evolutionApiRequest\n";
    exit(1);
}
$m = $ref->getMethod('evolutionApiRequest');
$m->setAccessible(true);

echo "Chamando GET /instances contra {$company['evolution_server_url']} ...\n";
$res = $m->invoke($controller, $company, '/instances', 'GET', null);
print_r($res);

if (isset($res['error'])) {
    echo "Erro: " . $res['error'] . "\n";
    exit(1);
}

echo "Fim do teste.\n";
