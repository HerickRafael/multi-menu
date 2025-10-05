<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Company.php';

class PublicProfileController extends Controller
{
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $sessName = function_exists('config') ? (config('session_name') ?? 'mm_session') : 'mm_session';

        if ($sessName && session_name() !== $sessName) {
            session_name($sessName);
        }
        @session_start();
    }

    private function guard(array $params): array
    {
        $slug = $params['slug'] ?? '';
        $company = Company::findBySlug($slug);

        if (!$company || empty($company['active'])) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        $this->ensureSession();
        $customer = $_SESSION['customer'] ?? null;

        if (!$customer || (int)($customer['company_id'] ?? 0) !== (int)$company['id']) {
            $redirect = base_url(rawurlencode((string)$company['slug'])) . '?login=1';
            header('Location: ' . $redirect);
            exit;
        }

        return [$company, $customer];
    }

    public function index(array $params)
    {
        [$company, $customer] = $this->guard($params);
        $slug = $params['slug'] ?? '';

        $addresses = [];

        if (!empty($_SESSION['customer_addresses']) && is_array($_SESSION['customer_addresses'])) {
            $cid = (int)$company['id'];
            $addresses = $_SESSION['customer_addresses'][$cid] ?? [];
        }

        return $this->view('public/profile', compact('company', 'customer', 'addresses', 'slug'));
    }

    public function update(array $params)
    {
        [$company, $customer] = $this->guard($params);

        $payload = $_POST['profile'] ?? [];

        if (!is_array($payload)) {
            $payload = [];
        }

        $updates = [
            'name'      => trim((string)($payload['name'] ?? $customer['name'] ?? '')),
            'whatsapp'  => trim((string)($payload['whatsapp'] ?? $customer['whatsapp'] ?? '')),
            'email'     => trim((string)($payload['email'] ?? $customer['email'] ?? '')),
            'birthdate' => trim((string)($payload['birthdate'] ?? $customer['birthdate'] ?? '')),
            'document'  => trim((string)($payload['document'] ?? $customer['document'] ?? '')),
            'notes'     => trim((string)($payload['notes'] ?? $customer['notes'] ?? '')),
        ];

        $customer = array_merge($customer, $updates);
        $_SESSION['customer'] = $customer;

        $slug = trim((string)$company['slug']);
        $redirect = base_url(($slug !== '' ? $slug . '/' : '') . 'profile?updated=1');
        header('Location: ' . $redirect);
        exit;
    }
}
