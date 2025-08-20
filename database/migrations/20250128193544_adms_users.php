<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsUsers extends AbstractMigration
{
    /**
     * Cria a tabela AdmsUsers.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_users` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `name`: Nome do usuário (não pode ser nulo)
     * - `email`: E-mail do usuário (não pode ser nulo)
     * - `username`: Nome de usuário (não pode ser nulo)
     * - `password`: Senha do usuário (não pode ser nulo)
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

        // Verifica se a tabela 'adms_users' não existe no banco de dados
        if (!$this->hasTable('adms_users')) {

            // Cria a tabela 'adms_users'
            $table = $this->table('adms_users');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                ->addColumn('email', 'string', ['null' => false])
                ->addColumn('username', 'string', ['null' => false])
                ->addColumn('password', 'string', ['null' => false])
                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->create();
        }
    }

    /**
     * Reverte a criação da tabela AdmsUsers.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_users` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Apagar a tabela adms_users
        $this->table('adms_users')->drop()->save();
    }
}
