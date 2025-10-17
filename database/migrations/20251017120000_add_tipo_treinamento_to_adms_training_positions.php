<?php

use Phinx\Migration\AbstractMigration;

class AddTipoTreinamentoToAdmsTrainingPositions extends AbstractMigration
{
    public function up(): void
    {
        // Adiciona coluna tipo_treinamento (Inicial|Continuo) com default 'Inicial'
        $table = $this->table('adms_training_positions');
        if (!$table->hasColumn('tipo_treinamento')) {
            $table->addColumn('tipo_treinamento', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'Inicial',
                'after' => 'obrigatorio',
            ])->update();
        }
    }

    public function down(): void
    {
        // Remove a coluna adicionada
        $table = $this->table('adms_training_positions');
        if ($table->hasColumn('tipo_treinamento')) {
            $table->removeColumn('tipo_treinamento')->update();
        }
    }
}


