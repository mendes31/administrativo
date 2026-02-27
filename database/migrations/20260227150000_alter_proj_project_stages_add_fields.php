<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Complementa a tabela proj_project_stages com campos necessários
 * para gestão de etapas dentro do projeto.
 *
 * Novos campos:
 * - activity             (Atividade)
 * - description          (Descrição)
 * - responsible_user_id  (Titular - usuário responsável)
 * - depends_on_stage_id  (Dependência de outra etapa do mesmo projeto)
 * - completed            (Etapa concluída)
 */
final class AlterProjProjectStagesAddFields extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('proj_project_stages')) {
            return;
        }

        $table = $this->table('proj_project_stages');

        if (!$table->hasColumn('activity')) {
            $table->addColumn('activity', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'name',
                'comment' => 'Atividade / tarefa da etapa',
            ]);
        }

        if (!$table->hasColumn('description')) {
            $table->addColumn('description', 'text', [
                'null' => true,
                'comment' => 'Descrição detalhada da etapa',
            ]);
        }

        if (!$table->hasColumn('responsible_user_id')) {
            $table->addColumn('responsible_user_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Usuário responsável (adms_users.id)',
            ]);
        }

        if (!$table->hasColumn('depends_on_stage_id')) {
            $table->addColumn('depends_on_stage_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Etapa da qual esta depende (proj_project_stages.id)',
            ]);
        }

        if (!$table->hasColumn('completed')) {
            $table->addColumn('completed', 'boolean', [
                'null' => false,
                'default' => 0,
                'comment' => '1 = etapa concluída',
            ]);
        }

        $table->update();

        // Foreign keys separadas para evitar problemas caso colunas já existam
        $table = $this->table('proj_project_stages');

        // FK para responsável
        $fkUserExists = false;
        $fks = $this->fetchAll("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                WHERE TABLE_NAME = 'proj_project_stages' AND COLUMN_NAME = 'responsible_user_id'");
        foreach ($fks as $fk) {
            if (!empty($fk['CONSTRAINT_NAME'])) {
                $fkUserExists = true;
                break;
            }
        }
        if (!$fkUserExists) {
            $table->addForeignKey('responsible_user_id', 'adms_users', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])->update();
        }

        // FK para dependência
        $fkDepExists = false;
        $fksDep = $this->fetchAll("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                   WHERE TABLE_NAME = 'proj_project_stages' AND COLUMN_NAME = 'depends_on_stage_id'");
        foreach ($fksDep as $fk) {
            if (!empty($fk['CONSTRAINT_NAME'])) {
                $fkDepExists = true;
                break;
            }
        }
        if (!$fkDepExists) {
            $table->addForeignKey('depends_on_stage_id', 'proj_project_stages', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('proj_project_stages')) {
            return;
        }

        $table = $this->table('proj_project_stages');

        // Remover FKs com segurança (se existirem)
        $columnsWithFk = ['responsible_user_id', 'depends_on_stage_id'];
        foreach ($columnsWithFk as $column) {
            $fks = $this->fetchAll("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                    WHERE TABLE_NAME = 'proj_project_stages' AND COLUMN_NAME = '{$column}'");
            foreach ($fks as $fk) {
                if (!empty($fk['CONSTRAINT_NAME'])) {
                    $table->dropForeignKey($column, $fk['CONSTRAINT_NAME']);
                }
            }
        }

        if ($table->hasColumn('completed')) {
            $table->removeColumn('completed');
        }
        if ($table->hasColumn('depends_on_stage_id')) {
            $table->removeColumn('depends_on_stage_id');
        }
        if ($table->hasColumn('responsible_user_id')) {
            $table->removeColumn('responsible_user_id');
        }
        if ($table->hasColumn('description')) {
            $table->removeColumn('description');
        }
        if ($table->hasColumn('activity')) {
            $table->removeColumn('activity');
        }

        $table->update();
    }
}

