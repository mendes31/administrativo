<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInstructorNameToAdmsTrainings extends AbstractMigration
{
    /**
     * Adiciona o campo instructor_name na tabela adms_trainings.
     */
    public function up(): void
    {
        if ($this->hasTable('adms_trainings') && !$this->table('adms_trainings')->hasColumn('instructor_name')) {
            $this->table('adms_trainings')
                ->addColumn('instructor_name', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => 'instructor_user_id',
                    'default' => null
                ])
                ->update();
        }
    }

    /**
     * Remove o campo instructor_name da tabela adms_trainings.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_trainings') && $this->table('adms_trainings')->hasColumn('instructor_name')) {
            $this->table('adms_trainings')
                ->removeColumn('instructor_name')
                ->update();
        }
    }
} 