<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsPackagesPages extends AbstractMigration
{
    
    public function up(): void
    {
        // Verifica se a tabela 'adms_packages_pages' não existe no banco de dados
        if(!$this->hasTable('adms_packages_pages')){
            
            // Cria a tabela 'adms_packages_pages'
            $table = $this->table('adms_packages_pages');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                 ->addColumn('obs', 'text', ['null' => true])
                 ->addColumn('created_at', 'timestamp')
                 ->addColumn('updated_at', 'timestamp')
                 ->create();

        }
    }
    /**
     * Reverter a criação da tabela AdmsPackagePàges
     * 
     * Este método remove a tabela
     * 
     * @return void
     */
    public function down() : void
    {
        // Remove a tabela dobanco de dados
        $this->table('adms_packages_pages')->drop()->save();  
    }

}