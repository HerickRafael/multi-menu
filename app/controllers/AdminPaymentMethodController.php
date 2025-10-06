<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/PaymentMethod.php';

class AdminPaymentMethodController extends Controller
{
    private function detectPixKeyType(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        $digits = preg_replace('/\D+/', '', $key);
        if (strlen($digits) === 11) {
            return 'cpf';
        }

        if (strlen($digits) === 14) {
            return 'cnpj';
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            return 'telefone';
        }

        return 'aleatoria';
    }

    private function normaliseMeta($rawMeta): array
    {
        $meta = [];

        if (is_array($rawMeta)) {
            foreach ($rawMeta as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }
                $value = trim((string)$value);
                if ($value === '') {
                    continue;
                }
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

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
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);
        $methods = PaymentMethod::allByCompany((int)$company['id']);

        $flash = $_SESSION['flash_payment'] ?? null;
        $old   = $_SESSION['old_payment'] ?? [
            'name' => '',
            'instructions' => '',
            'sort_order' => PaymentMethod::nextSortOrder((int)$company['id']),
            'active' => 1,
            'type' => 'credit',
            'meta' => [],
        ];
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
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : PaymentMethod::nextSortOrder((int)$company['id']);
        $active = isset($_POST['active']) ? 1 : 0;

        $allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix'];
        $type = trim($_POST['type'] ?? 'others');
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'others';
        }

        // normaliza meta e padroniza nome para Pix quando necessário
        $meta = $this->normaliseMeta($_POST['meta'] ?? []);
        if ($type === 'pix' && $name === '') {
            $name = 'Pix';
        }

        if ($name === '') {
            $this->errors(['Informe o nome do método de pagamento.']);
            $this->previous([
                'name' => $name,
                'instructions' => $instructions,
                'sort_order' => $sortOrder,
                'active' => $active,
                'type' => $type,
                'meta' => $meta,
            ]);
            $this->flash(['type' => 'error', 'message' => 'Não foi possível salvar o método.']);
            $this->redirectToIndex($company['slug']);
        }

        // mapear pix_key (e metadados) quando tipo = pix
        $pixKey = '';
        if ($type === 'pix') {
            $pixKey = trim($meta['px_key'] ?? '');
            if ($pixKey === '') {
                $pixKey = trim($_POST['pix_key'] ?? '');
            }
            if ($pixKey === '') {
                $nameFallback = trim($name);
                if ($nameFallback !== '' && strcasecmp($nameFallback, 'Pix') !== 0) {
                    $pixKey = $nameFallback; // usuário pode ter digitado a chave no campo de nome
                }
            }
            if ($pixKey !== '') {
                $meta['px_key'] = $pixKey;
                $meta['px_key_type'] = $this->detectPixKeyType($pixKey);
            } else {
                unset($meta['px_key'], $meta['px_key_type']);
            }

            $pixHolder = trim($meta['px_holder_name'] ?? ($_POST['pix_holder_name'] ?? ''));
            if ($pixHolder !== '') {
                $meta['px_holder_name'] = $pixHolder;
            }
        }

        if ($type !== 'pix') {
            unset($meta['px_key'], $meta['px_provider'], $meta['px_holder_name'], $meta['px_key_type']);
        }

        // para tipo pix, salva nome canônico
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

        // Carrega registro criado
        $created = PaymentMethod::findForCompany((int)$newId, (int)$company['id']);
        if ($created && isset($created['meta']) && is_string($created['meta'])) {
            $decodedMeta = json_decode($created['meta'], true);
            $created['meta'] = is_array($decodedMeta) ? $decodedMeta : [];
        }

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
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

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

        $allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix'];
        $type = trim($_POST['type'] ?? ($method['type'] ?? 'others'));
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'others';
        }

        // meta existente do registro (caso POST não envie nada)
        $existingMeta = [];
        if (!empty($method['meta'])) {
            $decodedMeta = json_decode((string)$method['meta'], true);
            if (is_array($decodedMeta)) {
                $existingMeta = $this->normaliseMeta($decodedMeta);
            }
        }

        // normaliza meta vinda do POST; se vier vazio, reaproveita a existente
        $meta = $this->normaliseMeta($_POST['meta'] ?? []);
        if (!$meta && $existingMeta) {
            $meta = $existingMeta;
        }

        $isAjax = $this->isAjaxRequest();
        $hasName = $name !== '';

        if ($type === 'pix' && $name === '') {
            $name = 'Pix';
            $hasName = true;
        }

        if (!$hasName && !$isAjax) {
            $this->flash(['type' => 'error', 'message' => 'Informe o nome do método.']);
            $this->redirectToIndex($company['slug']);
        }

        if ($isAjax && !$hasName && empty($_POST['meta']) && !isset($_POST['type']) && !isset($_POST['instructions'])) {
            // atualização apenas de toggle: preserva dados existentes
            $name = (string)$method['name'];
            $instructions = (string)($method['instructions'] ?? '');
            $sortOrder = (int)$method['sort_order'];
            $type = (string)($method['type'] ?? 'others');
            $meta = $existingMeta;
        }

        // mapear pix_key (e metadados) quando tipo = pix
        $pixKey = '';
        if ($type === 'pix') {
            $pixKey = trim($meta['px_key'] ?? '');
            if ($pixKey === '') {
                $pixKey = trim($_POST['pix_key'] ?? '');
            }
            if ($pixKey === '') {
                $nameFallback = trim($name);
                if ($nameFallback !== '' && strcasecmp($nameFallback, 'Pix') !== 0) {
                    $pixKey = $nameFallback; // usuário pode ter digitado a chave no campo de nome
                }
            }
            if ($pixKey !== '') {
                $meta['px_key'] = $pixKey;
                $meta['px_key_type'] = $this->detectPixKeyType($pixKey);
            } else {
                unset($meta['px_key'], $meta['px_key_type']);
            }

            $pixHolder = trim($meta['px_holder_name'] ?? ($_POST['pix_holder_name'] ?? ''));
            if ($pixHolder !== '') {
                $meta['px_holder_name'] = $pixHolder;
            }
        }

        if ($type !== 'pix') {
            unset($meta['px_key'], $meta['px_provider'], $meta['px_holder_name'], $meta['px_key_type']);
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
        if ($updated && isset($updated['meta']) && is_string($updated['meta'])) {
            $decodedMeta = json_decode($updated['meta'], true);
            $updated['meta'] = is_array($decodedMeta) ? $decodedMeta : [];
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'method' => $updated]);
            exit;
        }

        $this->flash(['type' => 'success', 'message' => 'Método atualizado.']);
        $this->redirectToIndex($company['slug']);
    }

    public function batchUpdate($params)
    {
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

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
        $slug = (string)($params['slug'] ?? '');
        [$user, $company] = $this->guard($slug);

        $id = (int)($params['id'] ?? 0);
        PaymentMethod::delete($id, (int)$company['id']);
        $this->flash(['type' => 'success', 'message' => 'Método removido.']);
        $this->redirectToIndex($company['slug']);
    }
}
