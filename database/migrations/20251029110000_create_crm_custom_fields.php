<?php

use Phinx\Migration\AbstractMigration;

class CreateCrmCustomFields extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de definição de campos customizados
        if (!$this->hasTable('crm_custom_fields')) {
            $table = $this->table('crm_custom_fields', ['id' => true]);
            
            $table->addColumn('entity_type', 'enum', [
                  'values' => ['partner', 'opportunity'],
                  'comment' => 'Tipo de entidade (parceiro ou oportunidade)'
              ])
              ->addColumn('field_name', 'string', ['limit' => 100, 'comment' => 'Nome do campo'])
              ->addColumn('field_label', 'string', ['limit' => 100, 'comment' => 'Rótulo exibido'])
              ->addColumn('field_type', 'enum', [
                  'values' => ['text', 'number', 'date', 'select', 'textarea', 'checkbox'],
                  'comment' => 'Tipo do campo'
              ])
              ->addColumn('field_options', 'text', ['null' => true, 'comment' => 'Opções para select (JSON)'])
              ->addColumn('is_required', 'boolean', ['default' => false])
              ->addColumn('display_order', 'integer', ['default' => 0])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('created_at', 'datetime', ['null' => true])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addIndex(['entity_type', 'field_name'], ['unique' => true])
              ->create();
        }

        // Tabela de valores dos campos customizados para parceiros
        if (!$this->hasTable('crm_custom_field_values_partners')) {
            $table = $this->table('crm_custom_field_values_partners', ['id' => true]);
            
            $table->addColumn('partner_id', 'integer', ['signed' => false])
              ->addColumn('custom_field_id', 'integer', ['signed' => false])
              ->addColumn('field_value', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['null' => true])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addForeignKey('partner_id', 'crm_partners', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('custom_field_id', 'crm_custom_fields', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['partner_id', 'custom_field_id'], ['unique' => true])
              ->create();
        }

        // Tabela de valores dos campos customizados para oportunidades
        if (!$this->hasTable('crm_custom_field_values_opportunities')) {
            $table = $this->table('crm_custom_field_values_opportunities', ['id' => true]);
            
            $table->addColumn('opportunity_id', 'integer', ['signed' => false])
              ->addColumn('custom_field_id', 'integer', ['signed' => false])
              ->addColumn('field_value', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['null' => true])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addForeignKey('opportunity_id', 'crm_opportunities', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('custom_field_id', 'crm_custom_fields', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['opportunity_id', 'custom_field_id'], ['unique' => true])
              ->create();
        }
    }
}

