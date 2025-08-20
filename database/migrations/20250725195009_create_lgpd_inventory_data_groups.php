<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLgpdInventoryDataGroups extends AbstractMigration
{
    /**
     * Cria a tabela `lgpd_inventory_data_groups`.
     *
     * Esta tabela relaciona os itens do inventário com os grupos de dados,
     * permitindo que um item do inventário tenha múltiplos grupos de dados
     * e vice-versa.
     *
     * @return void
     */
    public function up(): void
    {
        // Verifica se a tabela 'lgpd_inventory_data_groups' não existe no banco de dados
        if (!$this->hasTable('lgpd_inventory_data_groups')) {

            // Cria a tabela 'lgpd_inventory_data_groups'
            $table = $this->table('lgpd_inventory_data_groups');

            // Define as colunas da tabela
            $table->addColumn('lgpd_inventory_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do item do inventário'])
                ->addColumn('lgpd_data_group_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do grupo de dados'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('lgpd_inventory_id', 'lgpd_inventory', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('lgpd_data_group_id', 'lgpd_data_groups', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addIndex(['lgpd_inventory_id', 'lgpd_data_group_id'], ['unique' => true])
                ->create();
        }
    }

    /**
     * Remove a tabela `lgpd_inventory_data_groups`.
     *
     * @return void
     */
    public function down(): void
    {
        // Verifica se a tabela 'lgpd_inventory_data_groups' existe no banco de dados
        if ($this->hasTable('lgpd_inventory_data_groups')) {
            // Remove a tabela 'lgpd_inventory_data_groups'
            $this->table('lgpd_inventory_data_groups')->drop()->save();
        }
    }
}