<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmPartners extends AbstractMigration
{
    public function change()
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('crm_partners')) {
            return;
        }

        $table = $this->table('crm_partners');
        
        // Identificação
        $table->addColumn('code', 'string', ['limit' => 20])
              ->addColumn('name', 'string', ['limit' => 255])
              ->addColumn('trading_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('type_person', 'enum', ['values' => ['PF', 'PJ']])
              ->addColumn('document', 'string', ['limit' => 20, 'null' => true])
              
              // Contato
              ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('mobile', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('website', 'string', ['limit' => 255, 'null' => true])
              
              // Endereço
              ->addColumn('zip_code', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('number', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('complement', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('neighborhood', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('city', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('state', 'string', ['limit' => 2, 'null' => true])
              
              // Segmentação
              ->addColumn('segment', 'enum', ['values' => ['Farma', 'Suplementos', 'Ambos']])
              ->addColumn('partner_type', 'enum', ['values' => ['Lead', 'Cliente', 'Prospect'], 'default' => 'Lead'])
              ->addColumn('source', 'string', ['limit' => 100, 'null' => true])
              
              // Classificação
              ->addColumn('lead_score', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('priority', 'enum', ['values' => ['Baixa', 'Média', 'Alta', 'Urgente'], 'default' => 'Média'])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo', 'Bloqueado'], 'default' => 'Ativo'])
              
              // Relacionamento
              ->addColumn('responsible_user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('department_id', 'integer', ['null' => true, 'signed' => false])
              
              // Datas importantes
              ->addColumn('first_contact_date', 'datetime', ['null' => true])
              ->addColumn('last_contact_date', 'datetime', ['null' => true])
              ->addColumn('next_contact_date', 'datetime', ['null' => true])
              
              // Financeiro
              ->addColumn('estimated_revenue', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0])
              
              // Observações
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('tags', 'string', ['limit' => 500, 'null' => true])
              
              // Auditoria
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              
              // Índices
              ->addIndex(['code'], ['unique' => true])
              ->addIndex(['name'])
              ->addIndex(['document'], ['unique' => true])
              ->addIndex(['responsible_user_id'])
              ->addIndex(['segment'])
              ->addIndex(['partner_type'])
              ->addIndex(['status'])
              
              // Foreign Keys
              ->addForeignKey('responsible_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('department_id', 'adms_departments', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              
              ->create();
    }
}

