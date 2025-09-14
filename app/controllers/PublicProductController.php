<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Ingredient.php';

class PublicProductController extends Controller
{
    public function show($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);
        if (!$company || !$company['active']) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        $ingredients = Ingredient::listByProduct($id);
        return $this->view('public/product', compact('company', 'product', 'ingredients'));
    }

    public function customize($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);
        if (!$company || !$company['active']) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        return $this->view('public/customize', compact('company', 'product'));
    }
}
