<?php
// Router para mock da Evolution API usado com php -S
// Endpoints implementados (simples, em memória):
// POST /instances or POST /instance/createInstance -> cria instância (retorna instance_identifier, qr_code, status)
// GET /instances/{id}/qr or GET /instance/fetchInstances?instanceName=... -> retorna qr_code e status
// POST /instance/deleteInstance -> remove (retorna 200)

// armazenamento simples em arquivos temporários
$storageFile = sys_get_temp_dir() . '/mock_evolution_instances.json';
if (!file_exists($storageFile)) {
    file_put_contents($storageFile, json_encode([]));
}
$instances = json_decode(file_get_contents($storageFile), true);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');

function saveInstances($instances, $file)
{
    file_put_contents($file, json_encode($instances));
}

// helper para ler JSON body
function jsonBody()
{
    $b = file_get_contents('php://input');
    if (!$b) return null;
    $d = json_decode($b, true);
    return $d;
}

// Basic API key check (optional)
$expectedKey = 'testkey';
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0) {
        $h = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
        $headers[$h] = $v;
    }
}

if (isset($headers['Authentication-Api-Key']) && $headers['Authentication-Api-Key'] !== $expectedKey) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized - invalid API key']);
    exit;
}

if ($method === 'POST' && (preg_match('#^/instances$#', $path) || preg_match('#^/instance/createInstance$#', $path))) {
    $body = jsonBody() ?: [];
    $number = $body['number'] ?? $body['instanceName'] ?? null;
    if (!$number) {
        http_response_code(400);
        echo json_encode(['message' => 'number is required']);
        exit;
    }

    $id = bin2hex(random_bytes(6));
    $qr = base64_encode('QR_FOR_' . $number . '_' . $id);
    $inst = [
        'id' => $id,
        'instance_identifier' => $id,
        'number' => $number,
        'qr_code' => $qr,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $instances[$id] = $inst;
    saveInstances($instances, $storageFile);

    http_response_code(201);
    echo json_encode($inst);
    exit;
}

if ($method === 'GET' && preg_match('#^/instances/([^/]+)/qr$#', $path, $m)) {
    $ident = $m[1];
    if (!isset($instances[$ident])) {
        http_response_code(404);
        echo json_encode(['message' => 'not found']);
        exit;
    }

    // Simula que qr_code pode mudar para conectado
    $instances[$ident]['status'] = 'connected';
    saveInstances($instances, $storageFile);

    echo json_encode(['qr_code' => $instances[$ident]['qr_code'], 'status' => $instances[$ident]['status']]);
    exit;
}
// v2 style: fetch by query
if ($method === 'GET' && preg_match('#^/instance/fetchInstances$#', $path)) {
    $q = $_GET['instanceName'] ?? null;
    if (!$q) {
        // return all
        echo json_encode(array_values($instances));
        exit;
    }
    // find by id/instanceName
    if (isset($instances[$q])) {
        echo json_encode($instances[$q]);
        exit;
    }
    // also search by number
    foreach ($instances as $i) {
        if (($i['number'] ?? '') === $q) {
            echo json_encode($i);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['message' => 'not found']);
    exit;
}

// v2 style delete
if ($method === 'POST' && preg_match('#^/instance/deleteInstance$#', $path)) {
    $body = jsonBody() ?: [];
    $name = $body['instanceName'] ?? null;
    if (!$name) { http_response_code(400); echo json_encode(['message' => 'instanceName required']); exit; }
    if (isset($instances[$name])) { unset($instances[$name]); saveInstances($instances, $storageFile); echo json_encode(['ok' => true]); exit; }
    // try search
    foreach ($instances as $k => $v) { if (($v['number'] ?? '') === $name) { unset($instances[$k]); saveInstances($instances, $storageFile); echo json_encode(['ok' => true]); exit; } }
    http_response_code(404); echo json_encode(['message' => 'not found']); exit;
}

// default: show simple info
echo json_encode(['message' => 'mock evolution server running', 'path' => $path, 'method' => $method]);
