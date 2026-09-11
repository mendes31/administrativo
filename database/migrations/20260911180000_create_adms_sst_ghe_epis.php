<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstGheEpis extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_ghe_epis')) {
            return;
        }

        $this->table('adms_sst_ghe_epis')
            ->addColumn('adms_sst_ghe_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('adms_sst_epi_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('obrigatorio', 'boolean', ['default' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['adms_sst_ghe_id', 'adms_sst_epi_id'], ['unique' => true, 'name' => 'uq_sst_ghe_epi'])
            ->addForeignKey('adms_sst_ghe_id', 'adms_sst_ghe', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('adms_sst_epi_id', 'adms_sst_epis', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_ghe_epis')) {
            $this->table('adms_sst_ghe_epis')->drop()->save();
        }
    }
}
