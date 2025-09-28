<?php
// app/services/CartStorage.php

require_once __DIR__ . '/../core/Helpers.php';

class CartStorage
{
    private static $instance = null;

    private $redis = null;
    private $useRedis = false;
    private $ttl = 86400;

    private function __construct()
    {
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
            session_start();
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
    }

    public function clearCart(?string $sessionId = null): void
    {
        $sid = $sessionId ?: $this->ensureSession();
        if ($this->useRedis) {
            $this->redis->del($this->key($sid, 'cart'));
        }
        $_SESSION['cart'] = [];
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
    }
}
