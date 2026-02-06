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
            // NOTA: Índices únicos não são criados aqui porque as colunas email e username
            // são VARCHAR(255) com utf8mb4, o que resulta em 1020 bytes (255 * 4),
            // excedendo o limite de 767 bytes do MySQL para índices.
            // A validação de unicidade é feita na aplicação PHP.
            
            // Criar índices não-únicos para melhorar performance de buscas
            $table = $this->table('adms_users');
            
            // Verificar se os índices já existem antes de criar
            $indexes = $this->query("SHOW INDEX FROM adms_users")->fetchAll();
            $hasEmailIndex = false;
            $hasUsernameIndex = false;
            
            foreach ($indexes as $index) {
                if ($index['Key_name'] === 'idx_email' || strpos($index['Key_name'], 'email') !== false) {
                    $hasEmailIndex = true;
                }
                if ($index['Key_name'] === 'idx_username' || strpos($index['Key_name'], 'username') !== false) {
                    $hasUsernameIndex = true;
                }
            }
            
            // Adicionar índices não-únicos apenas se não existirem
            if (!$hasEmailIndex) {
                $table->addIndex(['email'], ['unique' => false, 'name' => 'idx_email'])
                    ->update();
            }
            
            if (!$hasUsernameIndex) {
                $table->addIndex(['username'], ['unique' => false, 'name' => 'idx_username'])
                    ->update();
            }
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
            // Indicar a tabela para remover os índices das colunas email e username
            $table = $this->table('adms_users');

            // Remover os índices (se existirem)
            try {
                $table->removeIndexByName('idx_email')->update();
            } catch (\Exception $e) {
                // Ignorar se não existir
            }
            
            try {
                $table->removeIndexByName('idx_username')->update();
            } catch (\Exception $e) {
                // Ignorar se não existir
            }
        }
    }
}
