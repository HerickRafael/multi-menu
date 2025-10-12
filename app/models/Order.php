<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Order
{
    public static function listByCompany(PDO $db, int $companyId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM orders WHERE company_id = :cid';
        $args = [':cid' => $companyId];

        if ($status) {
            $sql .= ' AND status = :st';
            $args[':st'] = $status;
        }
        $sql .= ' ORDER BY id DESC LIMIT :lim OFFSET :off';
        $st = $db->prepare($sql);

        foreach ($args as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $db, array $data): int
    {
        $defaults = [
            'company_id'     => 0,
            'customer_name'  => null,
            'customer_phone' => null,
            'subtotal'       => 0,
            'delivery_fee'   => 0,
            'discount'       => 0,
            'total'          => 0,
            'status'         => 'pending',
            'notes'          => null,
            'customer_address' => null,
        ];
        $payload = array_merge($defaults, $data);

        $sql = 'INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, total, status, notes, customer_address)
                VALUES (:cid,:name,:phone,:sub,:fee,:disc,:tot,:status,:notes,:address)';
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':cid'     => $payload['company_id'],
                ':name'    => $payload['customer_name'],
                ':phone'   => $payload['customer_phone'],
                ':sub'     => $payload['subtotal'],
                ':fee'     => $payload['delivery_fee'],
                ':disc'    => $payload['discount'],
                ':tot'     => $payload['total'],
                ':status'  => $payload['status'],
                ':notes'   => $payload['notes'],
                ':address' => $payload['customer_address'],
            ]);
        } catch (PDOException $e) {
            // fallback for databases sem coluna customer_address
            if (stripos($e->getMessage(), 'customer_address') !== false) {
                $stmt = $db->prepare('INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, total, status, notes)
                                      VALUES (:cid,:name,:phone,:sub,:fee,:disc,:tot,:status,:notes)');
                $stmt->execute([
                    ':cid'    => $payload['company_id'],
                    ':name'   => $payload['customer_name'],
                    ':phone'  => $payload['customer_phone'],
                    ':sub'    => $payload['subtotal'],
                    ':fee'    => $payload['delivery_fee'],
                    ':disc'   => $payload['discount'],
                    ':tot'    => $payload['total'],
                    ':status' => $payload['status'],
                    ':notes'  => $payload['notes'],
                ]);
            } else {
                throw $e;
            }
        }

        return (int)$db->lastInsertId();
    }

    public static function addItem(PDO $db, int $orderId, array $item): void
    {
        $sql = 'INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total)
                VALUES (:oid,:pid,:qty,:unit,:line)';
        $st = $db->prepare($sql);
        $st->execute([
            ':oid'  => $orderId,
            ':pid'  => $item['product_id'],
            ':qty'  => $item['quantity'],
            ':unit' => $item['unit_price'],
            ':line' => $item['line_total'],
        ]);
    }

    public static function findWithItems(PDO $db, int $orderId, int $companyId): ?array
    {
        $st = $db->prepare('SELECT * FROM orders WHERE id = ? AND company_id = ?');
        $st->execute([$orderId, $companyId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];

        return $order;
    }

    public static function findBasic(PDO $db, int $orderId, int $companyId): ?array
    {
        $st = $db->prepare('SELECT * FROM orders WHERE id = ? AND company_id = ?');
        $st->execute([$orderId, $companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function findForKds(PDO $db, int $orderId, int $companyId): ?array
    {
        $order = self::findBasic($db, $orderId, $companyId);

        if (!$order) {
            return null;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];

        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        return self::serializeForKds($order, true, $companyAvgTo);
    }

    public static function updateStatus(PDO $db, int $orderId, int $companyId, string $status): bool
    {
        $allowed = ['pending','paid','completed','canceled'];

        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $updated = false;
        $sql = 'UPDATE orders SET status = :status, status_changed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND company_id = :company';
        $stmt = $db->prepare($sql);
        try {
            $updated = $stmt->execute([
                ':status'  => $status,
                ':id'      => $orderId,
                ':company' => $companyId,
            ]);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'status_changed_at') !== false || stripos($e->getMessage(), 'updated_at') !== false) {
                $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id AND company_id = :company');
                $updated = $stmt->execute([
                    ':status'  => $status,
                    ':id'      => $orderId,
                    ':company' => $companyId,
                ]);
            } else {
                throw $e;
            }
        }

        if ($updated) {
            $eventType = $status === 'canceled' ? 'order.canceled' : 'order.status_changed';
            self::emitOrderEvent($db, $orderId, $companyId, $eventType);
        }

        return $updated;
    }

    public static function delete(PDO $db, int $orderId, int $companyId): bool
    {
        $st = $db->prepare('DELETE FROM orders WHERE id = ? AND company_id = ?');

        return $st->execute([$orderId, $companyId]);
    }

    public static function countByCompany(int $companyId): int
    {
        $pdo = db();
        $st = $pdo->prepare('SELECT COUNT(*) AS total FROM orders WHERE company_id = ?');
        $st->execute([$companyId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }

    public static function listRecentByCompany(int $companyId, int $limit = 8): array
    {
        $pdo = db();
        $sql = 'SELECT id, customer_name, total, status, created_at
                FROM orders
                WHERE company_id = :cid
                ORDER BY created_at DESC, id DESC
                LIMIT :lim';
        $st = $pdo->prepare($sql);
        $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function snapshot(PDO $db, int $companyId): array
    {
        $sql = "SELECT *
                FROM orders
                WHERE company_id = :cid
                  AND status IN ('pending','paid','completed','canceled')
                ORDER BY created_at ASC, id ASC
                LIMIT 150";
        $st = $db->prepare($sql);
        $st->execute([':cid' => $companyId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }
        $orderIds = array_map(static fn ($row) => (int)$row['id'], $rows);
        $itemsMap = self::itemsForOrders($db, $orderIds);
        // fetch company-specific SLA "avg_delivery_min_to" once to avoid repeated queries
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        $result = [];

        foreach ($rows as $row) {
            $row['items'] = $itemsMap[$row['id']] ?? [];
            $result[] = self::serializeForKds($row, true, $companyAvgTo);
        }

        return $result;
    }

    public static function snapshotDelta(PDO $db, int $companyId, string $sinceIso): array
    {
        $sinceIso = trim($sinceIso);
        $sinceTs = $sinceIso !== '' ? strtotime($sinceIso) : false;

        if (!$sinceTs) {
            return [
                'orders'       => self::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        $sinceDb = date('Y-m-d H:i:s', $sinceTs);
        $statusFilter = ['pending','paid','completed','canceled'];

        $rows = [];
        $executed = false;
        $expressions = [
            'COALESCE(status_changed_at, updated_at, created_at)',
            'COALESCE(updated_at, created_at)',
            'created_at',
        ];

        foreach ($expressions as $expr) {
            $sql = "SELECT *
                    FROM orders
                    WHERE company_id = :cid
                      AND $expr >= :since
                    ORDER BY created_at ASC, id ASC
                    LIMIT 150";
            try {
                $st = $db->prepare($sql);
                $st->execute([
                    ':cid'   => $companyId,
                    ':since' => $sinceDb,
                ]);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
                $executed = true;
                break;
            } catch (PDOException $e) {
                continue;
            }
        }

        if (!$executed) {
            return [
                'orders'       => self::snapshot($db, $companyId),
                'removed_ids'  => [],
                'full_refresh' => true,
            ];
        }

        if (!$rows) {
            return [
                'orders'       => [],
                'removed_ids'  => [],
                'full_refresh' => false,
            ];
        }

        $activeRows = [];
        $removed = [];

        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');

            if (!in_array($status, $statusFilter, true)) {
                $removed[] = (int)$row['id'];
                continue;
            }
            $activeRows[] = $row;
        }

        $orders = [];
        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        if ($activeRows) {
            $orderIds = array_map(static fn ($row) => (int)$row['id'], $activeRows);
            $itemsMap = self::itemsForOrders($db, $orderIds);

            foreach ($activeRows as $row) {
                $row['items'] = $itemsMap[$row['id']] ?? [];
                $orders[] = self::serializeForKds($row, true, $companyAvgTo);
            }
        }

        return [
            'orders'       => $orders,
            'removed_ids'  => $removed,
            'full_refresh' => false,
        ];
    }

    public static function latestChangeToken(PDO $db, int $companyId): ?string
    {
        $candidates = [
            'COALESCE(status_changed_at, updated_at, created_at)',
            'COALESCE(updated_at, created_at)',
            'created_at',
        ];

        foreach ($candidates as $expression) {
            $sql = "SELECT MAX($expression) AS last_change FROM orders WHERE company_id = :cid";
            try {
                $st = $db->prepare($sql);
                $st->execute([':cid' => $companyId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);

                if (!$row || empty($row['last_change'])) {
                    continue;
                }
                $ts = strtotime((string)$row['last_change']);

                if ($ts) {
                    return gmdate('c', $ts);
                }
            } catch (PDOException $e) {
                continue;
            }
        }

        return null;
    }

    private static function itemsForOrders(PDO $db, array $orderIds): array
    {
        if (!$orderIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "SELECT oi.*, p.name AS product_name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id IN ($placeholders)
                ORDER BY oi.id";
        $st = $db->prepare($sql);
        $st->execute($orderIds);
        $map = [];

        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $orderId = (int)$row['order_id'];

            if (!isset($map[$orderId])) {
                $map[$orderId] = [];
            }
            $map[$orderId][] = $row;
        }

        return $map;
    }

    public static function serializeForKds(array $order, bool $withItems = true, int $companyAvgTo = 0): array
    {
        $formatIso = function ($value) {
            if (!$value) {
                return null;
            }
            $ts = strtotime((string)$value);

            return $ts ? gmdate('c', $ts) : null;
        };

        $result = [
            'id' => (int)($order['id'] ?? 0),
            'company_id' => (int)($order['company_id'] ?? 0),
            'status' => (string)($order['status'] ?? 'pending'),
            'customer_name' => (string)($order['customer_name'] ?? ''),
            'customer_phone' => (string)($order['customer_phone'] ?? ''),
            'customer_address' => (string)($order['customer_address'] ?? ''),
            'notes' => $order['notes'] ?? '',
            'subtotal' => (float)($order['subtotal'] ?? 0),
            'delivery_fee' => (float)($order['delivery_fee'] ?? 0),
            'discount' => (float)($order['discount'] ?? 0),
            'total' => (float)($order['total'] ?? 0),
            'created_at' => $formatIso($order['created_at'] ?? null),
            'updated_at' => $formatIso($order['updated_at'] ?? null),
            'status_changed_at' => $formatIso($order['status_changed_at'] ?? null),
        ];

        if (!empty($order['sla_deadline'])) {
            $result['sla_deadline'] = $formatIso($order['sla_deadline']);
        } else {
            // prefer company-specific average "to" when provided, else fall back to config
            $slaMinutes = $companyAvgTo > 0 ? $companyAvgTo : (int)(function_exists('config') ? (config('kds_sla_minutes') ?? 20) : 20);
            $createdAt = $order['created_at'] ?? null;

            if ($slaMinutes > 0 && $createdAt) {
                $deadline = strtotime($createdAt . ' +' . $slaMinutes . ' minutes');

                if ($deadline) {
                    $result['sla_deadline'] = gmdate('c', $deadline);
                }
            }
        }

        if ($withItems) {
            $items = [];
            $source = $order['items'] ?? [];

            foreach ($source as $item) {
                $items[] = self::formatItemForKds($item);
            }
            $result['items'] = $items;
        }

        return $result;
    }

    private static function formatItemForKds(array $item): array
    {
        $name = $item['product_name'] ?? $item['name'] ?? '';
        $quantity = (int)($item['quantity'] ?? $item['qty'] ?? 0);
        $lineTotal = (float)($item['line_total'] ?? $item['total'] ?? 0);

        return [
            'id' => (int)($item['id'] ?? 0),
            'product_id' => (int)($item['product_id'] ?? 0),
            'name' => (string)$name,
            'qty' => $quantity,
            'quantity' => $quantity,
            'unit_price' => (float)($item['unit_price'] ?? 0),
            'line_total' => $lineTotal,
        ];
    }

    public static function emitOrderEvent(PDO $db, int $orderId, int $companyId, string $eventType): void
    {
        $order = self::findBasic($db, $orderId, $companyId);

        if (!$order) {
            return;
        }
        $items = self::itemsForOrders($db, [$orderId]);
        $order['items'] = $items[$orderId] ?? [];
        // fetch company-specific SLA "avg_delivery_min_to"
        $companyAvgTo = 0;
        try {
            $stc = $db->prepare('SELECT avg_delivery_min_to FROM companies WHERE id = ?');
            $stc->execute([$companyId]);
            $crow = $stc->fetch(PDO::FETCH_ASSOC);
            $companyAvgTo = isset($crow['avg_delivery_min_to']) ? (int)$crow['avg_delivery_min_to'] : 0;
        } catch (PDOException $e) {
            $companyAvgTo = 0;
        }

        $payload = [
            'order' => self::serializeForKds($order, true, $companyAvgTo),
            'created_at' => gmdate('c'),
        ];
        self::logEvent($db, $orderId, $companyId, $eventType, $order['status'] ?? null, $payload);

        // if order.created, trigger notifier (best-effort)
        if ($eventType === 'order.created') {
            try {
                require_once __DIR__ . '/../services/EvolutionNotifier.php';
                $companyRow = null;
                try {
                    $stc = $db->prepare('SELECT * FROM companies WHERE id = ?');
                    $stc->execute([$companyId]);
                    $companyRow = $stc->fetch(PDO::FETCH_ASSOC) ?: null;
                } catch (Throwable $e) {
                    $companyRow = null;
                }
                EvolutionNotifier::notifyOrderCreated($companyRow ?: ['id' => $companyId], self::serializeForKds($order, true, $companyAvgTo), $order['items']);
            } catch (Throwable $e) {
                error_log('Order emit notifier error: ' . $e->getMessage());
            }
        }
    }

    private static function logEvent(PDO $db, int $orderId, int $companyId, string $eventType, ?string $status, array $payload = []): void
    {
        $sql = 'INSERT INTO order_events (order_id, company_id, event_type, status, payload)
                VALUES (:order_id, :company_id, :event_type, :status, :payload)';
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':order_id'   => $orderId,
                ':company_id' => $companyId,
                ':event_type' => $eventType,
                ':status'     => $status,
                ':payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (PDOException $e) {
            // Banco sem tabela de eventos -> ignora
            if (stripos($e->getMessage(), 'order_events') === false) {
                throw $e;
            }
        }
    }

    public static function latestEvents(PDO $db, int $companyId, int $afterId = 0, int $limit = 100): array
    {
        try {
            $sql = 'SELECT id, order_id, company_id, event_type, status, payload, created_at
                    FROM order_events
                    WHERE company_id = :cid AND id > :after
                    ORDER BY id ASC
                    LIMIT :lim';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':cid', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':after', $afterId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'order_events') !== false) {
                return [];
            }
            throw $e;
        }

        $events = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payload = null;

            if (!empty($row['payload'])) {
                $decoded = json_decode($row['payload'], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                }
            }
            $events[] = [
                'id'         => (int)$row['id'],
                'order_id'   => (int)$row['order_id'],
                'company_id' => (int)$row['company_id'],
                'event_type' => $row['event_type'],
                'status'     => $row['status'],
                'payload'    => $payload,
                'created_at' => $row['created_at'],
            ];
        }

        return $events;
    }

    public static function lastEventId(PDO $db, int $companyId): int
    {
        try {
            $stmt = $db->prepare('SELECT MAX(id) AS max_id FROM order_events WHERE company_id = :cid');
            $stmt->execute([':cid' => $companyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($row['max_id'] ?? 0);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'order_events') !== false) {
                return 0;
            }
            throw $e;
        }
    }
}
