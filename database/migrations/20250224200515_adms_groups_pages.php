<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsGroupsPages extends AbstractMigration
{
    /**
     * Cria a tabela AdmsGroupsPages.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_groups_pages` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `name`: Nome do grupo (não pode ser nulo)
     * - `obs`: Observação sobre do grupo
     * - `created_at`: Timestamp da criação do registro
     * - `updated_at`: Timestamp da última atualização do registro
     *
     * Referência:
     * - https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * 
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_groups_pages' não existe no banco de dados
        if (!$this->hasTable('adms_groups_pages')) {
            // Cria a tabela 'adms_groups_pages'
            $table = $this->table('adms_groups_pages');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                ->addColumn('obs', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->create();
        }
    }

    /**
     * Reverte a criação da tabela AdmsGroupsPages.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_groups_pages` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_groups_pages' do banco de dados
        $this->table('adms_groups_pages')->drop()->save();
    }
}
