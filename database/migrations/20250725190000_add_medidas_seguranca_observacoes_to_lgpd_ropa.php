<?php
use Phinx\Migration\AbstractMigration;

class AddMedidasSegurancaObservacoesToLgpdRopa extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('lgpd_ropa');
        
        // Verificar se a coluna medidas_seguranca já existe
        if (!$table->hasColumn('medidas_seguranca')) {
            $table->addColumn('medidas_seguranca', 'text', [
                'null' => true,
                'comment' => 'Medidas de segurança implementadas'
            ]);
        }
        
        // Verificar se a coluna observacoes já existe
        if (!$table->hasColumn('observacoes')) {
            $table->addColumn('observacoes', 'text', [
                'null' => true,
                'comment' => 'Observações adicionais'
            ]);
        }
        
        $table->update();
    }

    public function down()
    {
        $table = $this->table('lgpd_ropa');
        
        // Remover a coluna medidas_seguranca se existir
        if ($table->hasColumn('medidas_seguranca')) {
            $table->removeColumn('medidas_seguranca');
        }
        
        // Remover a coluna observacoes se existir
        if ($table->hasColumn('observacoes')) {
            $table->removeColumn('observacoes');
        }
        
        $table->update();
    }
} 