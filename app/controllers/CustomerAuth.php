<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

require_once __DIR__ . '/../models/Customer.php';

class CustomerAuth extends BaseController
{
    protected function getCompanyBySlug(string $slug): ?array
    {
        // Ajuste ao seu schema
        $db = db_connect();
        return $db->table('companies')->where('slug', $slug)->get()->getRowArray() ?: null;
    }

    public function login(string $slug)
    {
        $company = $this->getCompanyBySlug($slug);
        if (!$company) {
            return $this->response->setStatusCode(404)->setBody('Empresa não encontrada');
        }

        $name     = trim($this->request->getPost('name') ?? $this->request->getPost('nome') ?? '');
        $whatsRaw = trim($this->request->getPost('whatsapp') ?? '');

        if ($name === '' || $whatsRaw === '') {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['ok'=>false, 'message'=>'Informe nome e WhatsApp.']);
        }

        $e164 = normalize_whatsapp_e164($whatsRaw);
        if ($e164 === '' || strlen($e164) < 12) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                ->setJSON(['ok'=>false, 'message'=>'WhatsApp inválido.']);
        }

        $now      = date('Y-m-d H:i:s');
        $customer = \Customer::findByCompanyAndE164((int)$company['id'], $e164);

        if (!$customer) {
            $id = \Customer::insert([
                'company_id'    => (int)$company['id'],
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'whatsapp_e164' => $e164,
                'created_at'    => $now,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ]);
            $customer = \Customer::findById($id);
        } else {
            \Customer::updateById((int)$customer['id'], [
                'name'          => $name,
                'whatsapp'      => $whatsRaw,
                'updated_at'    => $now,
                'last_login_at' => $now,
            ]);
            $customer = \Customer::findById((int)$customer['id']);
        }

        session()->set('customer', [
            'id'           => $customer['id'],
            'name'         => $customer['name'],
            'whatsapp'     => $customer['whatsapp'],
            'e164'         => $customer['whatsapp_e164'],
            'company_id'   => (int)$company['id'],
            'company_slug' => $slug,
            'login_at'     => $now,
        ]);

        // cookie 1 ano (opcional)
        $this->response->setCookie('mm_customer_e164', $customer['whatsapp_e164'], YEAR);

        return $this->response->setJSON(['ok'=>true]);
    }

    public function logout(string $slug)
    {
        session()->remove('customer');
        return $this->response->setJSON(['ok'=>true]);
    }

    public function me(string $slug)
    {
        $c = session('customer');
        return $this->response->setJSON(['logged'=>(bool)$c, 'customer'=>$c ?: null]);
    }
}
