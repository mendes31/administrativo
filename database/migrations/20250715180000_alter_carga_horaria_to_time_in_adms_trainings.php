<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterCargaHorariaToTimeInAdmsTrainings extends AbstractMigration
{
    /**
     * Altera o campo 'carga_horaria' de INT para TIME na tabela 'adms_trainings'.
     * Permite contabilizar horas e minutos (ex: 01:30, 00:45, etc).
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            // Altera o campo para TIME (permite valores como 01:30, 00:45, etc)
            $table->changeColumn('carga_horaria', 'time', [
                'null' => true,
                'default' => null,
                'comment' => 'Carga horária do treinamento (HH:MM:SS)'
            ])->update();
        }
    }

    /**
     * Reverte a alteração do campo 'carga_horaria' para INT.
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            $table->changeColumn('carga_horaria', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Carga horária do treinamento (em horas inteiras)'
            ])->update();
        }
    }
} 