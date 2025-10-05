<?php

declare(strict_types=1);
// app/services/CartStorage.php

require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/db.php';

class CartStorage
{
    private static $instance = null;

    private $redis = null;
    private $useRedis = false;
    private $ttl = 86400;
    private $dbAvailable = false;

    private function __construct()
    {
        if (function_exists('db')) {
            try {
                $pdo = db();

                if ($pdo instanceof PDO) {
                    $this->dbAvailable = true;
                }
            } catch (Throwable $e) {
                $this->dbAvailable = false;
            }
        }

        $cfg = config('redis') ?? [];
        $this->ttl = isset($cfg['ttl']) ? (int)$cfg['ttl'] : 86400;

        if (!empty($cfg['enabled']) && extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $host = $cfg['host'] ?? '127.0.0.1';
                $port = isset($cfg['port']) ? (int)$cfg['port'] : 6379;
                $timeout = isset($cfg['timeout']) ? (float)$cfg['timeout'] : 1.5;
                $redis->connect($host, $port, $timeout);

                if (!empty($cfg['password'])) {
                    $redis->auth($cfg['password']);
                }

                if (isset($cfg['database'])) {
                    $redis->select((int)$cfg['database']);
                }
                $this->redis = $redis;
                $this->useRedis = true;
            } catch (Throwable $e) {
                $this->redis = null;
                $this->useRedis = false;
            }
        }
    }

    public static function instance(): CartStorage
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function ensureSession(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (class_exists('Auth') && method_exists('Auth', 'start')) {
                Auth::start();
            } else {
                $name = config('session_name') ?? 'mm_session';

                if ($name && session_name() !== $name) {
                    session_name($name);
                }
                session_start();
            }
        }
        $sid = session_id();

        if (!$sid) {
            $sid = session_create_id();
            session_id($sid);
        }

        return $sid;
    }

    private function key(string $sessionId, string $type): string
    {
        return 'mm:' . $type . ':' . $sessionId;
    }

    private function decode($payload): ?array
    {
        if ($payload === false || $payload === null || $payload === '') {
            return null;
        }
        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function getCart(?string $sessionId = null): array
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $cached = $this->decode($this->redis->get($this->key($sid, 'cart')));

            if ($cached !== null) {
                $_SESSION['cart'] = $cached;

                return $cached;
            }
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $dbRow = $this->loadFromDatabase($sid);

            if ($dbRow !== null) {
                $_SESSION['cart'] = $dbRow['cart'];
                $_SESSION['customizations'] = $dbRow['customizations'];

                if ($this->useRedis) {
                    $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($_SESSION['cart']));
                    $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($_SESSION['customizations']));
                }
            }
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        return $_SESSION['cart'];
    }

    public function setCart(array $cart, ?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($cart));
        }
        $_SESSION['cart'] = $cart;

        $this->saveToDatabase($sid, $cart, $_SESSION['customizations'] ?? []);
    }

    public function clearCart(?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $this->redis->del($this->key($sid, 'cart'));
            $this->redis->del($this->key($sid, 'customizations'));
        }
        $_SESSION['cart'] = [];
        $_SESSION['customizations'] = [];
        $this->deleteFromDatabase($sid);
    }

    public function getCustomization(int $productId, ?string $sessionId = null): ?array
    {
        $customs = $this->getCustomizations($sessionId);

        return isset($customs[$productId]) && is_array($customs[$productId])
            ? $customs[$productId]
            : null;
    }

    public function setCustomization(int $productId, array $value, ?string $sessionId = null): void
    {
        $customs = $this->getCustomizations($sessionId);
        $customs[$productId] = $value;
        $this->saveCustomizations($customs, $sessionId);
    }

    public function removeCustomization(int $productId, ?string $sessionId = null): void
    {
        $customs = $this->getCustomizations($sessionId);

        if (isset($customs[$productId])) {
            unset($customs[$productId]);
            $this->saveCustomizations($customs, $sessionId);
        }
    }

    public function getCustomizations(?string $sessionId = null): array
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            $cached = $this->decode($this->redis->get($this->key($sid, 'customizations')));

            if ($cached !== null) {
                $_SESSION['customizations'] = $cached;

                return $cached;
            }
        }

        if (!isset($_SESSION['customizations']) || !is_array($_SESSION['customizations'])) {
            $dbRow = $this->loadFromDatabase($sid);

            if ($dbRow !== null) {
                $_SESSION['cart'] = $dbRow['cart'];
                $_SESSION['customizations'] = $dbRow['customizations'];

                if ($this->useRedis) {
                    $this->redis->setex($this->key($sid, 'cart'), $this->ttl, $this->encode($_SESSION['cart']));
                    $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($_SESSION['customizations']));
                }
            }
        }

        if (!isset($_SESSION['customizations']) || !is_array($_SESSION['customizations'])) {
            $_SESSION['customizations'] = [];
        }

        return $_SESSION['customizations'];
    }

    private function saveCustomizations(array $customs, ?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();

        if ($this->useRedis) {
            if ($customs) {
                $this->redis->setex($this->key($sid, 'customizations'), $this->ttl, $this->encode($customs));
            } else {
                $this->redis->del($this->key($sid, 'customizations'));
            }
        }
        $_SESSION['customizations'] = $customs;
        $this->saveToDatabase($sid, $_SESSION['cart'] ?? [], $customs);
    }

    private function loadFromDatabase(string $sid): ?array
    {
        if (!$this->dbAvailable) {
            return null;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return null;
            }
            $stmt = $pdo->prepare('SELECT cart_json, customizations_json FROM cart_sessions WHERE session_id = ? LIMIT 1');
            $stmt->execute([$sid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }
            $cart = $this->decode($row['cart_json']);
            $customs = $this->decode($row['customizations_json']);

            return [
                'cart' => $cart ?? [],
                'customizations' => $customs ?? [],
            ];
        } catch (Throwable $e) {
            $this->dbAvailable = false;

            return null;
        }
    }

    private function saveToDatabase(string $sid, array $cart, array $customs): void
    {
        if (!$this->dbAvailable) {
            return;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return;
            }

            if (!$cart && !$customs) {
                $this->deleteFromDatabase($sid);

                return;
            }
            $stmt = $pdo->prepare('INSERT INTO cart_sessions (session_id, cart_json, customizations_json, created_at, updated_at)
                                   VALUES (?, ?, ?, NOW(), NOW())
                                   ON DUPLICATE KEY UPDATE cart_json = VALUES(cart_json),
                                                           customizations_json = VALUES(customizations_json),
                                                           updated_at = NOW()');
            $stmt->execute([
                $sid,
                $this->encode($cart),
                $this->encode($customs),
            ]);
        } catch (Throwable $e) {
            $this->dbAvailable = false;
        }
    }

    private function deleteFromDatabase(string $sid): void
    {
        if (!$this->dbAvailable) {
            return;
        }
        try {
            $pdo = db();

            if (!$pdo instanceof PDO) {
                return;
            }
            $stmt = $pdo->prepare('DELETE FROM cart_sessions WHERE session_id = ?');
            $stmt->execute([$sid]);
        } catch (Throwable $e) {
            $this->dbAvailable = false;
        }
    }
}
