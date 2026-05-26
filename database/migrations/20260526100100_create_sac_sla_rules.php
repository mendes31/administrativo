<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacSlaRules extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_sla_rules')) {
            return;
        }

        $table = $this->table('sac_sla_rules');

        $table->addColumn('name', 'string', ['limit' => 150])
              ->addColumn('category_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('priority', 'enum', ['values' => ['Baixa', 'Média', 'Alta', 'Urgente'], 'null' => true])
              ->addColumn('response_time_hours', 'integer', ['signed' => false])
              ->addColumn('resolution_time_hours', 'integer', ['signed' => false])
              ->addColumn('escalation_enabled', 'boolean', ['default' => 0])
              ->addColumn('escalation_after_hours', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('escalation_user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('is_active', 'boolean', ['default' => 1])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['category_id'])
              ->addIndex(['priority'])
              ->addIndex(['is_active'])

              ->addForeignKey('category_id', 'sac_categories', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('escalation_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
