<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsAccessLevelsPages extends AbstractMigration
{

    /**
     * Cria a tabela `adms_access_levels_pages`.
     *
     * Este método cria a tabela `adms_access_levels_pages` com as colunas `adms_access_level_id`, 
     * `adms_page_id`, `created_at` e `updated_at`. Também define chaves estrangeiras para 
     * `adms_access_level_id` e `adms_page_id`, vinculando às tabelas `adms_access_levels` e 
     * `adms_pages`, respectivamente. As chaves estrangeiras têm as seguintes regras: 
     * - `adms_access_level_id`: `RESTRICT` em deleção e `CASCADE` em atualização.
     * - `adms_page_id`: `RESTRICT` em deleção e `CASCADE` em atualização.
     *
     * @return void
     */
    public function up(): void
    {

        // Verifica se a tabela 'adms_access_levels_pages' não existe no banco de dados
        if (!$this->hasTable('adms_access_levels_pages')) {

            // Cria a tabela 'adms_access_levels_pages'
            $table = $this->table('adms_access_levels_pages');

            // Define as colunas da tabela
            $table->addColumn('permission', 'boolean', ['null' => false, 'default' => 0, 'comment' => 'Permissão para o nível de acesso acessar a página: 1 - acesso permitido, 0 - acesso negado'])

                ->addColumn('adms_access_level_id', 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey('adms_access_level_id', 'adms_access_levels', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                ->addColumn('adms_page_id', 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey('adms_page_id', 'adms_pages', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->create();
        }
    }

    /**
     * Reverte a criação da tabela AdmsAccessLevelsPages.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_access_levels_pages` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Remove a tabela 'adms_access_levels_pages' do banco de dados
        $this->table('adms_access_levels_pages')->drop()->save();
    }
}
