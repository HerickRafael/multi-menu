<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/PaymentMethod.php';

class AdminPaymentMethodController extends Controller
{
    private function guard(string $slug): array
    {
        Auth::start();
        $user = Auth::user();

        if (!$user) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($user['role'] !== 'root' && (int)($user['company_id'] ?? 0) !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$user, $company];
    }

    private function flash(array $payload): void
    {
        $_SESSION['flash_payment'] = $payload;
    }

    private function previous(array $payload): void
    {
        $_SESSION['old_payment'] = $payload;
    }

    private function errors(array $payload): void
    {
        $_SESSION['errors_payment'] = $payload;
    }

    private function redirectToIndex(string $slug): void
    {
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/payment-methods'));
        exit;
    }

    public function index($params)
    {
        [$user, $company] = $this->guard($params['slug']);
        $methods = PaymentMethod::allByCompany((int)$company['id']);

        $flash = $_SESSION['flash_payment'] ?? null;
        $old   = $_SESSION['old_payment'] ?? ['name' => '', 'instructions' => '', 'sort_order' => PaymentMethod::nextSortOrder((int)$company['id']), 'active' => 1];
        $errors = $_SESSION['errors_payment'] ?? [];

        unset($_SESSION['flash_payment'], $_SESSION['old_payment'], $_SESSION['errors_payment']);

        $title = 'Métodos de pagamento - ' . ($company['name'] ?? '');

        return $this->view('admin/payments/index', compact('company', 'user', 'methods', 'flash', 'old', 'errors', 'title'));
    }

    private function isAjaxRequest(): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (!empty($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }

    public function store($params)
    {
        [$user, $company] = $this->guard($params['slug']);

        $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : PaymentMethod::nextSortOrder((int)$company['id']);
        $active = isset($_POST['active']) ? 1 : 0;
        $type = trim($_POST['type'] ?? 'others');
        $meta = [];
        if (!empty($_POST['meta']) && is_array($_POST['meta'])) {
            $meta = $_POST['meta'];
        }

        if ($name === '') {
            $this->errors(['Informe o nome do método de pagamento.']);
            $this->previous([
                'name' => $name,
                'instructions' => $instructions,
                'sort_order' => $sortOrder,
                'active' => $active,
            ]);
            $this->flash(['type' => 'error', 'message' => 'Não foi possível salvar o método.']);
            $this->redirectToIndex($company['slug']);
        }

        // map pix_key: if type is pix, accept explicit pix_key or fall back to name field
        $pixKey = trim($_POST['pix_key'] ?? '');
        if ($type === 'pix' && $pixKey === '') {
            $pixKey = $name; // user typed the key into the name field when label changed
        }

        // for records of type 'pix' store a canonical name
        $saveName = $type === 'pix' ? 'Pix' : $name;

        $newId = PaymentMethod::create([
            'company_id' => (int)$company['id'],
            'name' => $saveName,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
            'type' => $type,
            'meta' => $meta,
            'pix_key' => $pixKey ?: null,
        ]);

        // Load the created record
        $created = PaymentMethod::findForCompany((int)$newId, (int)$company['id']);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'method' => $created]);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Método adicionado com sucesso.']);
        $this->redirectToIndex($company['slug']);
    }

    public function update($params)
    {
        [$user, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);
        $method = PaymentMethod::findForCompany($id, (int)$company['id']);

        if (!$method) {
            $this->flash(['type' => 'error', 'message' => 'Método não encontrado.']);
            $this->redirectToIndex($company['slug']);
        }

        $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : (int)$method['sort_order'];
        $active = isset($_POST['active']) ? 1 : 0;
        $type = trim($_POST['type'] ?? ($method['type'] ?? 'others'));
        $meta = [];
        if (!empty($_POST['meta']) && is_array($_POST['meta'])) {
            $meta = $_POST['meta'];
        } elseif (!empty($method['meta'])) {
            $meta = json_decode((string)$method['meta'], true) ?: [];
        }

        if ($name === '') {
            $this->flash(['type' => 'error', 'message' => 'Informe o nome do método.']);
            $this->redirectToIndex($company['slug']);
        }

        // map pix_key for update
        $pixKey = trim($_POST['pix_key'] ?? '');
        if ($type === 'pix' && $pixKey === '') {
            $pixKey = $name; // user may have typed the key into name field
        }

        $saveName = $type === 'pix' ? 'Pix' : $name;

        PaymentMethod::update($id, (int)$company['id'], [
            'name' => $saveName,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
            'type' => $type,
            'meta' => $meta,
            'pix_key' => $pixKey ?: null,
        ]);

        $updated = PaymentMethod::findForCompany($id, (int)$company['id']);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'method' => $updated]);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Método atualizado.']);
        $this->redirectToIndex($company['slug']);
    }

    public function batchUpdate($params)
    {
        [$user, $company] = $this->guard($params['slug']);

        $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;

        // perform update
        require_once __DIR__ . '/../models/PaymentMethod.php';
        try {
            PaymentMethod::setAllActiveForCompany((int)$company['id'], $active);
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $this->flash(['type' => 'error', 'message' => 'Erro ao atualizar métodos.']);
            $this->redirectToIndex($company['slug']);
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Métodos atualizados.']);
        $this->redirectToIndex($company['slug']);
    }

    public function destroy($params)
    {
        [$user, $company] = $this->guard($params['slug']);
        $id = (int)($params['id'] ?? 0);
        PaymentMethod::delete($id, (int)$company['id']);
        $this->flash(['type' => 'success', 'message' => 'Método removido.']);
        $this->redirectToIndex($company['slug']);
    }
}
