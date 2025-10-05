<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/AuthCustomer.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../core/Helpers.php';

class CustomerAuthController extends Controller
{
    /**
     * Busca a empresa pelo slug.
     * Ajuste o nome da tabela/campos se necessário.
     */
    protected function findCompanyBySlug(string $slug): ?array
    {
        return Customer::findCompanyBySlug($slug);
    }

    /**
     * POST /{slug}/customer-login
     * Campos: name, whatsapp
     */
    public function login(array $params): void
    {
        // garante sessão
        AuthCustomer::start();

        $slug = $params['slug'] ?? null;

        if (!$slug) {
            $this->json(['ok' => false, 'message' => 'Empresa inválida.'], 400);
        }

        $company = $this->findCompanyBySlug($slug);

        if (!$company) {
            $this->json(['ok' => false, 'message' => 'Empresa não encontrada.'], 404);
        }

        $name     = trim($_POST['name'] ?? $_POST['nome'] ?? '');
        $whatsRaw = trim($_POST['whatsapp'] ?? '');

        if ($name === '' || $whatsRaw === '') {
            $this->json(['ok' => false, 'message' => 'Informe nome e WhatsApp.'], 400);
        }

        $e164 = normalize_whatsapp_e164($whatsRaw);

        if ($e164 === '' || strlen($e164) < 12) {
            $this->json(['ok' => false, 'message' => 'WhatsApp inválido.'], 400);
        }

        $now = date('Y-m-d H:i:s');

        // procura cliente por (company_id, whatsapp_e164)
        $customer = Customer::findByCompanyAndE164((int)$company['id'], $e164);

        if (!$customer) {
            // cria
            $id = Customer::insert([
                'company_id'    => (int)$company['id'],
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'whatsapp_e164' => $e164,
                'created_at'    => $now,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ]);
            $customer = Customer::findById((int)$id);
        } else {
            // atualiza
            Customer::updateById((int)$customer['id'], [
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ]);
            $customer = Customer::findById((int)$customer['id']);
        }

        // evita fixation e salva sessão com escopo da empresa
        session_regenerate_id(true);
        // salva sessão com escopo da empresa
        $_SESSION['customer'] = [
            'id'           => (int)$customer['id'],
            'name'         => $customer['name'],
            'whatsapp'     => $customer['whatsapp'],
            'e164'         => $customer['whatsapp_e164'],
            'company_id'   => (int)$company['id'],
            'company_slug' => $slug,
            'login_at'     => $now,
        ];

        // cookie 1 ano (opcional)
        setcookie('mm_customer_e164', $customer['whatsapp_e164'], [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $defaultRedirect = base_url(rawurlencode($slug));
        $redirectTarget = trim($_POST['redirect_to'] ?? '');
        $redirectUrl = $defaultRedirect;

        if ($redirectTarget !== '') {
            if ($redirectTarget[0] === '/') {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $redirectUrl = $scheme . '://' . $host . $redirectTarget;
            } elseif (preg_match('~^https?://~i', $redirectTarget)) {
                $parsed = parse_url($redirectTarget);
                $host = $_SERVER['HTTP_HOST'] ?? '';

                if (!empty($parsed['host']) && strcasecmp($parsed['host'], $host) === 0) {
                    $redirectUrl = $redirectTarget;
                }
            } else {
                $redirectUrl = base_url(ltrim($redirectTarget, '/'));
            }
        }

        $wantJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                 || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantJson) {
            $this->json(['ok' => true, 'redirect' => $redirectUrl]);
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * POST /{slug}/customer-logout
     */
    public function logout(array $params): void
    {
        AuthCustomer::start();
        $slug = $params['slug'] ?? '';
        unset($_SESSION['customer']);
        setcookie('mm_customer_e164', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_regenerate_id(true);

        $homeUrl = $slug ? base_url(rawurlencode($slug)) : base_url();
        $wantJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                 || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantJson) {
            $this->json(['ok' => true]);
        }
        header('Location: ' . $homeUrl);
        exit;
    }

    /**
     * GET /{slug}/customer-me
     */
    public function me(array $params): void
    {
        AuthCustomer::start();
        $c = $_SESSION['customer'] ?? null;
        $this->json(['logged' => (bool)$c, 'customer' => $c ?: null]);
    }

    /**
     * Utilitário para responder JSON (caso seu Controller base não tenha).
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
