<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSstPlanosAcao extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_sst_planos_acao')) {
            return;
        }

        $this->table('adms_sst_planos_acao')
            ->addColumn('adms_sst_acidente_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('responsavel_adms_user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('prazo', 'date', ['null' => true])
            ->addColumn('data_conclusao', 'date', ['null' => true])
            ->addColumn('status', 'enum', [
                'values' => ['Pendente', 'Em andamento', 'Concluído', 'Cancelado'],
                'default' => 'Pendente',
            ])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['adms_sst_acidente_id'])
            ->addIndex(['status'])
            ->addIndex(['prazo'])
            ->addIndex(['responsavel_adms_user_id'])
            ->addForeignKey('adms_sst_acidente_id', 'adms_sst_acidentes', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('responsavel_adms_user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }
}
