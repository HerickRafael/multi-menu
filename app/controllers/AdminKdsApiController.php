<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Company.php';

class AdminKdsApiController extends Controller
{
    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized']);
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['error' => 'empresa_invalida']);
            exit;
        }

        if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'forbidden']);
            exit;
        }

        return [$user, $company];
    }

    public function snapshot($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        $sinceParam = isset($_GET['since']) ? trim((string)$_GET['since']) : '';

        if ($sinceParam !== '') {
            $delta = Order::snapshotDelta($db, $companyId, $sinceParam);
        } else {
            $delta = [
                'orders'       => Order::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        $lastEventId = Order::lastEventId($db, $companyId);
        $payload = [
            'orders'        => $delta['orders'],
            'removed_ids'   => $delta['removed_ids'] ?? [],
            'full_refresh'  => !empty($delta['full_refresh']),
            'server_time'   => gmdate('c'),
            'last_event_id' => $lastEventId,
        ];

        $syncToken = Order::latestChangeToken($db, $companyId);

        if ($syncToken) {
            $payload['sync_token'] = $syncToken;
        }

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function events($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        ignore_user_abort(false);
        @set_time_limit(0);
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $lastId = 0;

        if (isset($_GET['last_id'])) {
            $lastId = max($lastId, (int)$_GET['last_id']);
        }

        if (!empty($_SERVER['HTTP_LAST_EVENT_ID'])) {
            $lastId = max($lastId, (int)$_SERVER['HTTP_LAST_EVENT_ID']);
        }

        $start = time();
        $heartbeat = time();
        $timeout = 15;

        while (!connection_aborted() && (time() - $start) < $timeout) {
            $events = Order::latestEvents($db, $companyId, $lastId, 200);

            if ($events) {
                foreach ($events as $event) {
                    $lastId = (int)$event['id'];
                    $payload = [
                        'order_id'   => (int)$event['order_id'],
                        'status'     => $event['status'],
                        'event_type' => $event['event_type'],
                        'created_at' => $event['created_at'],
                        'payload'    => $event['payload'] ?? null,
                    ];
                    $this->emitEvent($lastId, $event['event_type'], $payload);
                }
                $heartbeat = time();
            }

            if (time() - $heartbeat >= 25) {
                $this->emitEvent($lastId, 'keepalive', ['time' => gmdate('c')]);
                $heartbeat = time();
            }

            if ((time() - $start) >= $timeout) {
                break;
            }

            if (!$events) {
                usleep(1000000);
            }
        }

        $this->emitEvent($lastId, 'stream_end', ['time' => gmdate('c')]);
        exit;
    }

    public function status($params)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['error' => 'method_not_allowed']);
            exit;
        }

        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);
        $db = $this->db();
        $companyId = (int)$company['id'];

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $orderId = (int)($input['order_id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($orderId <= 0 || $status === '') {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_payload']);
            exit;
        }

        $ok = Order::updateStatus($db, $orderId, $companyId, $status);

        if (!$ok) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_status']);
            exit;
        }

        $order = Order::findForKds($db, $orderId, $companyId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'order'   => $order,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function metrics($params)
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(204);
            exit;
        }

        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $payload = json_decode(file_get_contents('php://input'), true);

        if (!is_array($payload)) {
            $payload = [];
        }
        $payload['company_id'] = (int)$company['id'];
        $payload['timestamp'] = gmdate('c');
        error_log('[KDS metrics] ' . json_encode($payload));

        http_response_code(204);
        exit;
    }

    private function emitEvent(int $id, string $type, array $data): void
    {
        echo 'id: ' . $id . "\n";
        echo 'event: ' . $type . "\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        @ob_flush();
        @flush();
    }
}
