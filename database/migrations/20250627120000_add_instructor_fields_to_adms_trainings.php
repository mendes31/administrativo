<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInstructorFieldsToAdmsTrainings extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if (!$table->hasColumn('instructor_user_id')) {
                $table->addColumn('instructor_user_id', 'integer', [
                    'null' => true,
                    'after' => 'instrutor',
                    'comment' => 'ID do usuário instrutor (colaborador interno)'
                ]);
            }
            if (!$table->hasColumn('instructor_email')) {
                $table->addColumn('instructor_email', 'string', [
                    'null' => true,
                    'limit' => 255,
                    'after' => 'instructor_user_id',
                    'comment' => 'E-mail do instrutor (interno ou externo)'
                ]);
            }
            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if ($table->hasColumn('instructor_user_id')) {
                $table->removeColumn('instructor_user_id');
            }
            if ($table->hasColumn('instructor_email')) {
                $table->removeColumn('instructor_email');
            }
            $table->update();
        }
    }
} 