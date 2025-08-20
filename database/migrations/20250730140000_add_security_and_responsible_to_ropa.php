<?php
use Phinx\Migration\AbstractMigration;

class AddSecurityAndResponsibleToRopa extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('lgpd_ropa');
        // Adicionar campo para medidas de segurança (somente se não existir)
        if (!$table->hasColumn('medidas_seguranca')) {
            $table->addColumn('medidas_seguranca', 'text', [
                'null' => true,
                'comment' => 'Medidas de segurança específicas para esta atividade'
            ]);
        }

        // Adicionar campo para responsável (somente se não existir)
        if (!$table->hasColumn('responsavel')) {
            $table->addColumn('responsavel', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Responsável pela atividade/ROPA'
            ]);
        }

        // Adicionar campo para observações (somente se não existir)
        if (!$table->hasColumn('observacoes')) {
            $table->addColumn('observacoes', 'text', [
                'null' => true,
                'comment' => 'Observações específicas da atividade'
            ]);
        }

        $table->update();
    }
} 