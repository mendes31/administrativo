<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsLogs extends AbstractMigration
{
    /**
     * Cria a tabela AdmsLogs.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_logs` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `id`: ID do log (não pode ser nulo)
     * - `date`: data
     * - `time`: Hora 
     * - `table`: Tabela  
     * - `action`: Ação 
     * - `adms_user_id`: Id do usuário 
     * - `id_reg`: Id do registro
     * - `description`: Descrição
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_logs' não existe no banco de dados
        if (!$this->hasTable('adms_logs')) {
            // Cria a tabela 'adms_logs'
            $table = $this->table('adms_logs');

            // Define as colunas da tabela
            $table->addColumn('date', 'date', ['null' => false])
                ->addColumn('time', 'time', ['null' => false])
                ->addColumn('table_name', 'string', ['null' => false])
                ->addColumn('action', 'string', ['null' => false])
                ->addColumn('user_id', 'integer', ['null' => false])
                ->addColumn('record_id', 'integer', ['null' => false])
                ->addColumn('description', 'text', ['null' => false])

                ->create();

            // $table->addColumn('name', 'string', ['null' => false])
            //     ->addColumn('controller', 'string', ['null' => false])
            //     ->addColumn('controller_url', 'string', ['null' => false])
            //     ->addColumn('directory', 'string', ['null' => false])

            //     ->addColumn('obs', 'text', ['null' => true])

            //     ->addColumn('page_status', 'boolean', ['null' => false, 'default' => 0, 'comment' => 'Página ativa 1 e página inativa 0'])

            //     ->addColumn('public_page', 'boolean', ['null' => false, 'default' => 0, 'comment' => 'Página publica 1 e página privada 0'])

            //     ->addColumn('adms_packages_page_id', 'integer', ['null' => false, 'signed' => false])
            //     ->addForeignKey('adms_packages_page_id', 'adms_packages_pages', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

            //     ->addColumn('adms_groups_page_id', 'integer', ['null' => false, 'signed' => false])
            //     ->addForeignKey('adms_groups_page_id', 'adms_groups_pages', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

            //     ->addColumn('created_at', 'timestamp')
            //     ->addColumn('updated_at', 'timestamp')
            //     ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_logs.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_logs` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_logs' do banco de dados
        $this->table('adms_logs')->drop()->save();
    }
}
