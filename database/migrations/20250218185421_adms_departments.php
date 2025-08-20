<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsDepartments extends AbstractMigration
{
    /**
     * Cria a tabela AdmsDepartments
     * 
     * Este método é executado durante a aplicação da migração para criar a tabeala `adms_departments`no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `name`: Nome do nível de acesso (não pode ser nulo)
     * - `order_levels`: Ordem do nível de acesso (não pode ser nulo)
     * `create_at`: Timestamp da criaç~çao do registro 
     * `update_at`: Timestamp da última atualização do registro
     * 
     * Referencia:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_departments' não existe no banco de dados
        if(!$this->hasTable('adms_departments')){
            // Cria a tabela 'adms_departments'
            $table = $this->table('adms_departments');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                    ->addColumn('create_at', 'timestamp')
                    ->addColumn('update_at', 'timestamp')
                    ->addIndex(['name'], ['unique' => true, 'name' => 'idx_unique_name']) // Adiciona i índice único com o nome específico
                    ->create();

        }
    }

    /**
     * Reverter a criação da tabela AdmsAccessLevels
     * 
     * Este método é executado durante a reversão da migração para remover a tabela `adms_access_levels` do banco de dados.
     * 
     * @return void 
     * 
     */
    public function down(): void 
    {
        // Remove a tabela 'adms_departments' do banco de dados
        $this->table('adms_departments')->drop()->save();
    }

}

