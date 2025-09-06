<?php namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table         = 'customers';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'company_id','name','whatsapp','whatsapp_e164','created_at','updated_at','last_login_at'
    ];
    protected $useTimestamps = false;

    public function findByCompanyAndWhatsappE164(int $companyId, string $e164)
    {
        return $this->where('company_id', $companyId)
                    ->where('whatsapp_e164', $e164)
                    ->first();
    }
}
