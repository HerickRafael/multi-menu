<?php
// app/controllers/PublicProductController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Ingredient.php';

class PublicProductController extends Controller
{
    /**
     * GET /{slug}/produto/{id}
     * Mostra a página pública do produto.
     * - Carrega empresa por slug
     * - Valida que o produto pertence à empresa e está ativo
     * - Carrega ingredientes simples
     * - Carrega grupos de opções (combo) + itens (se o produto for do tipo != 'simple')
     */
    public function show($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        // Empresa
        $company = Company::findBySlug($slug);
        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        // Produto
        $product = Product::find($id);
        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        // Ingredientes (lista simples)
        $ingredients = Ingredient::listByProduct($id);

        // Grupos de opções (combo) — somente se tipo != 'simple'
        $groups = [];
        $type = $product['type'] ?? 'simple';
        if ($type !== 'simple' && method_exists('Product', 'getComboGroupsWithItems')) {
            $groups = Product::getComboGroupsWithItems($id);
        }

        // Renderiza a view pública
        // A view espera: $company, $product, $ingredients, $groups
        return $this->view('public/product', compact('company', 'product', 'ingredients', 'groups'));
    }

    /**
     * (Opcional) GET /{slug}/produto/{id}/customizar
     * Deixe este método se você adicionou a rota de customização.
     * Aqui você pode carregar dados extras (mods/addons) quando tiver os modelos correspondentes.
     */
    public function customize($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);
        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        $product = Product::find($id);
        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        // Se você tiver tabelas/modelos de mods/addons, carregue-os aqui.
        // Exemplo:
        // $mods = Mods::listByProduct($id);
        // $addons = Addon::listByCompany($company['id']);
        // return $this->view('public/customize', compact('company','product','mods','addons'));

        // Por enquanto, só redireciona de volta ao produto
        redirect(base_url($slug.'/produto/'.$id));
        return;
    }

    /**
     * (Opcional) POST /{slug}/produto/{id}/customizar
     * Persiste a customização escolhida pelo cliente (se necessário) ou já encaminha ao carrinho.
     */
    public function saveCustomization($params)
    {
        // Implemente conforme sua modelagem (ex.: salvar em sessão/carrinho).
        // $slug = $params['slug'] ?? null;
        // $id   = isset($params['id']) ? (int)$params['id'] : 0;
        // $payload = $_POST; // sanitizar/validar
        // ...
        http_response_code(501);
        echo "Not Implemented";
    }
}
