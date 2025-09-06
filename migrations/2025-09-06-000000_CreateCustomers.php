<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'auto_increment'=>true],
            'company_id'       => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'name'             => ['type'=>'VARCHAR','constraint'=>120],
            'whatsapp'         => ['type'=>'VARCHAR','constraint'=>20],
            'whatsapp_e164'    => ['type'=>'VARCHAR','constraint'=>20],
            'created_at'       => ['type'=>'DATETIME','null'=>false,'default'=>'CURRENT_TIMESTAMP'],
            'updated_at'       => ['type'=>'DATETIME','null'=>true],
            'last_login_at'    => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id','whatsapp_e164'], 'uk_company_whatsapp');
        $this->forge->createTable('customers', true);
    }

    public function down()
    {
        $this->forge->dropTable('customers', true);
    }
}
