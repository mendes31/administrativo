<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Formações acadêmicas e cursos vinculados aos usuários.
 *
 * O comprovante fica em armazenamento privado; a tabela guarda somente os
 * metadados necessários para download autenticado.
 */
final class CreateAdmsUserEducations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users') || $this->hasTable('adms_user_educations')) {
            return;
        }

        $this->table('adms_user_educations', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('adms_user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('tipo', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('curso', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('instituicao', 'string', ['limit' => 191, 'null' => true])
            ->addColumn('situacao', 'string', ['limit' => 30, 'null' => false, 'default' => 'concluido'])
            ->addColumn('data_inicio', 'date', ['null' => true])
            ->addColumn('data_conclusao', 'date', ['null' => true])
            ->addColumn('carga_horaria', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('comprovante_path', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('comprovante_nome_original', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('comprovante_mime', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('comprovante_tamanho', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['adms_user_id', 'tipo'], ['name' => 'idx_user_educations_user_type'])
            ->addIndex(['curso'], ['name' => 'idx_user_educations_course'])
            ->addForeignKey('adms_user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('updated_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_user_educations')) {
            $this->table('adms_user_educations')->drop()->save();
        }
    }
}
