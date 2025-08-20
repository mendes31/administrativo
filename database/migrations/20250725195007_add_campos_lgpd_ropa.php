<?php
use Phinx\Migration\AbstractMigration;

class AddCamposLgpdRopa extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('lgpd_ropa');
        
        // Verificar se a coluna processing_purpose já existe
        if (!$table->hasColumn('processing_purpose')) {
            $table->addColumn('processing_purpose', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Finalidade do processamento'
            ]);
        }
        
        // Verificar se a coluna data_subject já existe
        if (!$table->hasColumn('data_subject')) {
            $table->addColumn('data_subject', 'string', [
                'limit' => 100,
                'null' => true,
                'comment' => 'Titulares dos dados'
            ]);
        }
        
        // Verificar se a coluna personal_data já existe
        if (!$table->hasColumn('personal_data')) {
            $table->addColumn('personal_data', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Dados tratados'
            ]);
        }
        
        // Verificar se a coluna sharing já existe
        if (!$table->hasColumn('sharing')) {
            $table->addColumn('sharing', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Compartilhamento com terceiros'
            ]);
        }
        
        // Verificar se a coluna inventory_id já existe
        if (!$table->hasColumn('inventory_id')) {
            $table->addColumn('inventory_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Relaciona com Inventário'
            ]);
        }
        
        $table->update();
        
        // Adicionar foreign key se a coluna foi criada
        if ($table->hasColumn('inventory_id')) {
            $this->execute('ALTER TABLE lgpd_ropa ADD CONSTRAINT fk_lgpd_ropa_inventory_id FOREIGN KEY (inventory_id) REFERENCES lgpd_inventory(id) ON DELETE SET NULL ON UPDATE NO ACTION');
        }
    }

    public function down()
    {
        $table = $this->table('lgpd_ropa');
        
        // Remover foreign key se existir
        try {
            $this->execute('ALTER TABLE lgpd_ropa DROP FOREIGN KEY fk_lgpd_ropa_inventory_id');
        } catch (Exception $e) {
            // Foreign key não existe, continuar
        }
        
        // Remover a coluna inventory_id se existir
        if ($table->hasColumn('inventory_id')) {
            $table->removeColumn('inventory_id');
        }
        
        // Remover a coluna sharing se existir
        if ($table->hasColumn('sharing')) {
            $table->removeColumn('sharing');
        }
        
        // Remover a coluna personal_data se existir
        if ($table->hasColumn('personal_data')) {
            $table->removeColumn('personal_data');
        }
        
        // Remover a coluna data_subject se existir
        if ($table->hasColumn('data_subject')) {
            $table->removeColumn('data_subject');
        }
        
        // Remover a coluna processing_purpose se existir
        if ($table->hasColumn('processing_purpose')) {
            $table->removeColumn('processing_purpose');
        }
        
        $table->update();
    }
}