<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPrazoTreinamentoToAdmsTrainings extends AbstractMigration
{
    /**
     * Adiciona o campo 'prazo_treinamento' e remove o campo 'validade' da tabela 'adms_trainings'.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if (!$table->hasColumn('prazo_treinamento')) {
                $table->addColumn('prazo_treinamento', 'integer', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'versao',
                    'comment' => 'Prazo em dias para realizar o primeiro treinamento após o vínculo'
                ]);
            }
            if ($table->hasColumn('validade')) {
                $table->removeColumn('validade');
            }
            $table->update();
        }
    }

    /**
     * Reverte as alterações (remove 'prazo_treinamento' e adiciona 'validade').
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if ($table->hasColumn('prazo_treinamento')) {
                $table->removeColumn('prazo_treinamento');
            }
            if (!$table->hasColumn('validade')) {
                $table->addColumn('validade', 'date', [
                    'null' => true,
                    'default' => null,
                    'after' => 'versao',
                    'comment' => 'Campo removido para uso do prazo em dias'
                ]);
            }
            $table->update();
        }
    }
} 