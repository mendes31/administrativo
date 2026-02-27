<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tabela principal de projetos.
 */
final class CreateProjProjects extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_projects')) {
            return;
        }

        $this->table('proj_projects')
            ->addColumn('type', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'INTERNAL',
                'comment' => 'INTERNAL ou EXTERNAL',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Nome do projeto',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'INICIADO',
                'comment' => 'Status macro do projeto (INICIADO, ATRASADO, SUSPENSO, CANCELADO, CONCLUIDO, ENCERRADO)',
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('expected_end_date', 'date', [
                'null' => true,
                'default' => null,
                'comment' => 'Data prevista de término',
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
                'default' => null,
                'comment' => 'Data real de término',
            ])
            ->addColumn('open_activities', 'integer', [
                'null' => false,
                'default' => 0,
                'signed' => false,
                'comment' => 'Quantidade de atividades em aberto (pode ser calculado)',
            ])
            ->addColumn('percent_complete', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'default' => 0,
                'comment' => 'Percentual concluído do projeto (0-100)',
            ])
            // Relação com Parceiros de Negócio (PN) - mantida sem FK direta para preservar compatibilidade
            ->addColumn('pn_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'ID do Parceiro de Negócio (cliente/fornecedor) se aplicável',
            ])
            ->addColumn('pn_code', 'string', [
                'limit' => 60,
                'null' => true,
                'comment' => 'Código do PN (se integrado com ERP ou módulo de PN)',
            ])
            ->addColumn('pn_name', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Nome do PN',
            ])
            // Responsáveis internos
            ->addColumn('contact_user_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Pessoa de contato interna (adms_users.id)',
            ])
            ->addColumn('owner_user_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Responsável pelo projeto (adms_users.id)',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'comment' => 'Descrição geral / escopo do projeto',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => 1,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['type'])
            ->addIndex(['status'])
            ->addIndex(['owner_user_id'])
            ->addIndex(['pn_id'])
            ->addForeignKey('contact_user_id', 'adms_users', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('owner_user_id', 'adms_users', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_projects')) {
            $this->table('proj_projects')->drop()->save();
        }
    }
}

