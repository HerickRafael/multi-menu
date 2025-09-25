<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\CompanyService;
use App\Application\Services\OrderService;
use App\Application\Services\ProductService;
use App\Core\Auth;
use App\Core\Controller;

class AdminOrdersController extends Controller
{
    private CompanyService $companies;

    private OrderService $orders;

    private ProductService $products;

    public function __construct()
    {
        $this->companies = new CompanyService();
        $this->orders = new OrderService();
        $this->products = new ProductService();
    }

    /** Valida sessão, empresa e retorna [$u, $company] */
    private function guard(string $slug): array
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = $this->companies->findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$u, $company];
    }

    public function index($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $status = $_GET['status'] ?? null;
        $orders = $this->orders->listByCompany($db, (int)$company['id'], $status, 50, 0);

        return $this->view('admin/orders/index', [
            'orders'     => $orders,
            'status'     => $status,
            'company'    => $company,
            'activeSlug' => $company['slug'],
        ]);
    }

    public function show($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_GET['id'] ?? 0);
        $order = $this->orders->findWithItems($db, $orderId, (int)$company['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';

            return;
        }

        return $this->view('admin/orders/show', [
            'order'      => $order,
            'company'    => $company,
            'activeSlug' => $company['slug'],
        ]);
    }

    public function setStatus($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($_POST['id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        if ($this->orders->updateStatus($db, $orderId, (int)$company['id'], $status)) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
            exit;
        }
        http_response_code(400);
        echo 'Não foi possível atualizar o status';
    }

    public function create($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $products = $this->products->listByCompany((int)$company['id']);
        $defaults = [
            'customer_name'  => '',
            'customer_phone' => '',
            'notes'          => '',
            'delivery_fee'   => 0,
            'discount'       => 0,
        ];

        return $this->view('admin/orders/form', [
            'products'   => $products,
            'defaults'   => $defaults,
            'company'    => $company,
            'activeSlug' => $company['slug'],
        ]);
    }

    public function store($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $customer_name  = trim($_POST['customer_name']  ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $notes          = trim($_POST['notes']          ?? '');
        $delivery_fee   = (float)($_POST['delivery_fee'] ?? 0);
        $discount       = (float)($_POST['discount']     ?? 0);

        $product_ids = $_POST['product_id'] ?? [];
        $quantities  = $_POST['quantity']   ?? [];

        if (!$customer_name) {
            http_response_code(400);
            echo 'Informe o nome do cliente.';

            return;
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$i] ?? 0);

            if ($pid <= 0 || $qty <= 0) {
                continue;
            }

            $prod = $this->products->find($pid);

            if (!$prod || (int)$prod['company_id'] !== (int)$company['id']) {
                continue;
            }

            $unit = (float)($prod['promo_price'] ?: $prod['price']);
            $line = $unit * $qty;

            $items[] = [
                'product_id' => $pid,
                'quantity'   => $qty,
                'unit_price' => $unit,
                'line_total' => $line,
            ];
            $subtotal += $line;
        }

        if (empty($items)) {
            http_response_code(400);
            echo 'Adicione ao menos um item.';

            return;
        }

        $total = max(0, $subtotal + $delivery_fee - $discount);

        $orderId = $this->orders->create($db, [
            'company_id'     => (int)$company['id'],
            'customer_name'  => $customer_name,
            'customer_phone' => $customer_phone,
            'subtotal'       => $subtotal,
            'delivery_fee'   => $delivery_fee,
            'discount'       => $discount,
            'total'          => $total,
            'status'         => 'pending',
            'notes'          => $notes,
        ]);

        foreach ($items as $it) {
            $this->orders->addItem($db, $orderId, $it);
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders/show?id=' . $orderId));
        exit;
    }

    public function destroy($params)
    {
        $slug = $params['slug'];
        [$u, $company] = $this->guard($slug);
        $db = $this->db();

        $orderId = (int)($params['id'] ?? 0);

        if ($orderId > 0 && $this->orders->delete($db, $orderId, (int)$company['id'])) {
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/orders'));
            exit;
        }

        http_response_code(400);
        echo 'Não foi possível excluir o pedido.';
    }
}
