<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * @method bool hasTable(string $tableName)
 * @method object table(string $tableName)
 */
final class AddTrainingVersioningFields extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        $table = $this->table('adms_trainings');

        if (!$table->hasColumn('training_family_key')) {
            $table->addColumn('training_family_key', 'string', [
                'limit' => 80,
                'null' => true,
                'default' => null,
                'comment' => 'Chave estável da família de versões do treinamento',
                'after' => 'codigo',
            ])->update();
        }

        $table = $this->table('adms_trainings');
        if (!$table->hasColumn('parent_training_id')) {
            $table->addColumn('parent_training_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'comment' => 'ID da versão imediatamente anterior',
                'after' => 'versao',
            ])->update();
        }

        $table = $this->table('adms_trainings');
        if (!$table->hasColumn('is_current_version')) {
            $table->addColumn('is_current_version', 'boolean', [
                'null' => false,
                'default' => 1,
                'comment' => 'Indica se esta versão é a atual da família',
                'after' => 'ativo',
            ])->update();
        }

        $table = $this->table('adms_trainings');
        if (!$table->hasColumn('change_summary')) {
            $table->addColumn('change_summary', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Resumo das alterações em relação à versão anterior',
                'after' => 'is_current_version',
            ])->update();
        }

        $table = $this->table('adms_trainings');
        if (!$table->hasColumn('require_retraining')) {
            $table->addColumn('require_retraining', 'boolean', [
                'null' => false,
                'default' => 1,
                'comment' => 'Se a nova versão exige retreinamento dos já treinados',
                'after' => 'change_summary',
            ])->update();
        }

        $table = $this->table('adms_trainings');
        if (!$table->hasIndex(['training_family_key', 'is_current_version'])) {
            $table->addIndex(['training_family_key', 'is_current_version'], [
                'name' => 'idx_training_family_current',
            ])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            return;
        }

        $table = $this->table('adms_trainings');
        if ($table->hasIndex(['training_family_key', 'is_current_version'])) {
            $table->removeIndex(['training_family_key', 'is_current_version'])->update();
        }

        $table = $this->table('adms_trainings');
        if ($table->hasColumn('require_retraining')) {
            $table->removeColumn('require_retraining')->update();
        }

        $table = $this->table('adms_trainings');
        if ($table->hasColumn('change_summary')) {
            $table->removeColumn('change_summary')->update();
        }

        $table = $this->table('adms_trainings');
        if ($table->hasColumn('is_current_version')) {
            $table->removeColumn('is_current_version')->update();
        }

        $table = $this->table('adms_trainings');
        if ($table->hasColumn('parent_training_id')) {
            $table->removeColumn('parent_training_id')->update();
        }

        $table = $this->table('adms_trainings');
        if ($table->hasColumn('training_family_key')) {
            $table->removeColumn('training_family_key')->update();
        }
    }
}

