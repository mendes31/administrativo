<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsBranches extends AbstractMigration
{
    /**
     * Cria a tabela `adms_branches`.
     *
     * Esta tabela armazena as filiais/unidades da empresa.
     */
    public function up(): void
    {
        if (!$this->hasTable('adms_branches')) {
            $table = $this->table('adms_branches');
            
            $table->addColumn('name', 'string', [
                'null' => false,
                'limit' => 100,
                'comment' => 'Nome da filial/unidade'
            ])
            ->addColumn('code', 'string', [
                'null' => false,
                'limit' => 10,
                'comment' => 'Código único da filial'
            ])
            ->addColumn('address', 'text', [
                'null' => true,
                'comment' => 'Endereço da filial'
            ])
            ->addColumn('phone', 'string', [
                'null' => true,
                'limit' => 20,
                'comment' => 'Telefone da filial'
            ])
            ->addColumn('email', 'string', [
                'null' => true,
                'limit' => 100,
                'comment' => 'E-mail da filial'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => 1,
                'comment' => 'Status da filial: 1 - ativa, 0 - inativa'
            ])
            ->addColumn('created_at', 'timestamp')
            ->addColumn('updated_at', 'timestamp')
            ->addIndex(['code'], ['unique' => true])
            ->create();
        }
    }

    /**
     * Remove a tabela `adms_branches`.
     */
    public function down(): void
    {
        $this->table('adms_branches')->drop()->save();
    }
} 