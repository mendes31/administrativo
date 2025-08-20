<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDataLimitePrimeiroTreinamentoToAdmsTrainingUsers extends AbstractMigration
{
    /**
     * Adiciona o campo 'data_limite_primeiro_treinamento' na tabela 'adms_training_users'.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if (!$table->hasColumn('data_limite_primeiro_treinamento')) {
                $table->addColumn('data_limite_primeiro_treinamento', 'date', [
                    'null' => true,
                    'after' => 'created_at',
                    'comment' => 'Data limite para realizar o primeiro treinamento após o vínculo'
                ])->update();
            }
        }
    }

    /**
     * Remove o campo 'data_limite_primeiro_treinamento' da tabela.
     *
     * @return void
     */
    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if ($table->hasColumn('data_limite_primeiro_treinamento')) {
                $table->removeColumn('data_limite_primeiro_treinamento')->update();
            }
        }
    }
} 