<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsPositions extends AbstractMigration
{
    /**
     * Cria a tabela AdmsPositions
     * 
     * Este método é executado durante a aplicação da migração para criar a tabeala `adms_positions`no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `name`: Nome do cargo (não pode ser nulo)
     * `created_at`: Timestamp da criaç~çao do registro 
     * `updated_at`: Timestamp da última atualização do registro
     * 
     * Referencia:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_positions' não existe no banco de dados
        if(!$this->hasTable('adms_positions')){
            // Cria a tabela 'adms_departments'
            $table = $this->table('adms_positions');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                    ->addColumn('created_at', 'timestamp')
                    ->addColumn('updated_at', 'timestamp')
                    ->addIndex(['name'], ['unique' => true, 'name' => 'idx_unique_name']) // Adiciona i índice único com o nome específico
                    ->create();

        }
    }

    /**
     * Reverter a criação da tabela adms_positions
     * 
     * Este método é executado durante a reversão da migração para remover a tabela `adms_positions` do banco de dados.
     * 
     * @return void 
     * 
     */
    public function down(): void 
    {
        // Remove a tabela 'adms_departments' do banco de dados
        $this->table('adms_positions')->drop()->save();
    }

}