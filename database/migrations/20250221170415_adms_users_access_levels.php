<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsUsersAccessLevels extends AbstractMigration
{
    /**
     * Cria a tabela AdmsUsersAcessLevels.
     * 
     * Este método é executado durante a aplicação da migração para criar a tabeala `adms_users_acess_levels`no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `adms_user_id`: Chave estrangeira como referencia a chave primaria da tabela 'adms_users'
     * - `adms_access_level_id`:Chave estrangeira como referencia a chave primaria da tabela 'adms_access_levels'
     * `created_at`: Timestamp da criaç~çao do registro 
     * `updated_at`: Timestamp da última atualização do registro
     * 
     * Referencia:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_users_access_levels' não existe no banco de dados
        if(!$this->hasTable('adms_users_access_levels')){
            // Cria a tabela 'adms_access_levels'
            $table = $this->table('adms_users_access_levels');

            // Define as colunas da tabela
            $table->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                    ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                    ->addColumn('adms_access_level_id', 'integer', ['null' => false, 'signed' => false])
                    ->addForeignKey('adms_access_level_id', 'adms_access_levels', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                    ->addColumn('created_at', 'timestamp')
                    ->addColumn('updated_at', 'timestamp')
                    ->create();

        }
    }
    /**
     * Reverter a criação da tabela AdmsUsersAccessLevels
     * 
     * Este método é executado durante a reversão da migração para remover a tabela `adms_users_access_levels` do banco de dados.
     * 
     * @return void 
     * 
     */
    public function down(): void 
    {
        // Remove a tabela 'adms_access_levels' do banco de dados
        $this->table('adms_users_access_levels')->drop()->save();
    }
}
