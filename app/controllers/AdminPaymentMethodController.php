<?php

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

    public function store($params)
    {
        [$user, $company] = $this->guard($params['slug']);

        $name = trim($_POST['name'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sortOrder = isset($_POST['sort_order']) && $_POST['sort_order'] !== ''
            ? (int)$_POST['sort_order']
            : PaymentMethod::nextSortOrder((int)$company['id']);
        $active = isset($_POST['active']) ? 1 : 0;

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

        PaymentMethod::create([
            'company_id' => (int)$company['id'],
            'name' => $name,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
        ]);

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

        if ($name === '') {
            $this->flash(['type' => 'error', 'message' => 'Informe o nome do método.']);
            $this->redirectToIndex($company['slug']);
        }

        PaymentMethod::update($id, (int)$company['id'], [
            'name' => $name,
            'instructions' => $instructions !== '' ? $instructions : null,
            'sort_order' => $sortOrder,
            'active' => $active,
        ]);

        $this->flash(['type' => 'success', 'message' => 'Método atualizado.']);
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
