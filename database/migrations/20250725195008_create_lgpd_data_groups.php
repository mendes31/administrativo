<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLgpdDataGroups extends AbstractMigration
{
    /**
     * Cria a tabela `lgpd_data_groups`.
     *
     * Esta tabela armazena os grupos de dados padronizados para LGPD,
     * facilitando o cadastro no inventário e garantindo consistência
     * entre Inventário → ROPA → Data Mapping.
     *
     * @return void
     */
    public function up(): void
    {
        // Verifica se a tabela 'lgpd_data_groups' não existe no banco de dados
        if (!$this->hasTable('lgpd_data_groups')) {

            // Cria a tabela 'lgpd_data_groups'
            $table = $this->table('lgpd_data_groups');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Nome do grupo de dados (ex: Identificação pessoal, Contato, Financeiro)'])
                ->addColumn('category', 'enum', ['values' => ['Pessoal', 'Sensível'], 'default' => 'Pessoal', 'comment' => 'Categoria do dado: Pessoal ou Sensível'])
                ->addColumn('example_fields', 'text', ['null' => true, 'comment' => 'Exemplos de campos incluídos no grupo (ex: Nome, CPF, RG)'])
                ->addColumn('is_sensitive', 'boolean', ['default' => false, 'comment' => 'Indica se o grupo contém dados sensíveis'])
                ->addColumn('notes', 'text', ['null' => true, 'comment' => 'Observações sobre o grupo de dados'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['name'], ['unique' => true])
                ->addIndex(['category'])
                ->addIndex(['is_sensitive'])
                ->create();
        }
    }

    /**
     * Remove a tabela `lgpd_data_groups`.
     *
     * @return void
     */
    public function down(): void
    {
        // Verifica se a tabela 'lgpd_data_groups' existe no banco de dados
        if ($this->hasTable('lgpd_data_groups')) {
            // Remove a tabela 'lgpd_data_groups'
            $this->table('lgpd_data_groups')->drop()->save();
        }
    }
}