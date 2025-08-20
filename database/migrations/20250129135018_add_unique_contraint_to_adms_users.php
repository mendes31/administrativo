<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueContraintToAdmsUsers extends AbstractMigration
{
    /**
     * Adiciona restrições de unicidade às colunas `email` e `username` da tabela `adms_users`.
     *
     * Este método é executado durante a aplicação da migração para garantir que os valores das colunas `email`
     * e `username` sejam únicos na tabela `adms_users`. Se a tabela existe, índices únicos são adicionados
     * às colunas para evitar valores duplicados.
     *
     * @return void
     */
    public function up(): void
    {
        // Acessar o IF quando a tabela existe no banco de dados
        if ($this->hasTable('adms_users')) {

            // Alterar a tabela para adicionar índices únicos
            $table = $this->table('adms_users');

            // Adicionar indices únicos às colunas email e username
            // 'name' => 'idx_unique_email' - nomear o indice único

            $table->addIndex(['email'], ['unique' => true, 'name' => 'idx_unique_email'])
                ->addIndex(['username'], ['unique' => true, 'name' => 'idx_unique_username'])
                ->update();
        }
    }
    /**
     * Remove as restrições de unicidade das colunas `email` e `username` da tabela `adms_users`.
     *
     * Este método é executado durante a reversão da migração para remover os índices únicos das colunas
     * `email` e `username`. Se a tabela existe, os índices únicos são removidos.
     *
     * @return void
     */
    public function down(): void
    {
        // Acessa o IF quando a tabela existe no banco de dados
        if ($this->hasTable('adms_users')) {

            // Indicar a tabela para remover os indices punicos das colunas email e username
            $table = $this->table('adms_users');

            // Remover os indices unicos
            $table->removeIndexByName('idx_unique_email')
                ->removeIndexByName('idx_unique_username')
                ->update();
        }
    }
}
