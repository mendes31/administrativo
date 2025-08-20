<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsUsersDepartments extends AbstractMigration
{
    /**
     * Cria a tabela AdmsUsersDepartments.
     * 
     * Este método é executado durante a aplicação da migração para criar a tabeala `adms_users_acess_levels`no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `adms_user_id`: Chave estrangeira como referencia a chave primaria da tabela 'adms_users'
     * - `adms_departments_id`:Chave estrangeira como referencia a chave primaria da tabela 'adms_departments'
     * `created_at`: Timestamp da criaç~çao do registro 
     * `updated_at`: Timestamp da última atualização do registro
     * 
     * Referencia:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_users_departments' não existe no banco de dados
        if (!$this->hasTable('adms_users_departments')) {
            // Cria a tabela 'adms_access_levels'
            $table = $this->table('adms_users_departments');

            // Define as colunas da tabela
            $table->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                ->addColumn('adms_department_id', 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey('adms_department_id', 'adms_departments', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])

                ->addColumn('created_at', 'timestamp')
                ->addColumn('updated_at', 'timestamp')
                ->create();
        }
    }
    
     /**
     * Reverter a criação da tabela AdmsUsersDepartments
     * 
     * Este método é executado durante a reversão da migração para remover a tabela `adms_users_departments` do banco de dados.
     * 
     * @return void 
     * 
     */
    public function down(): void 
    {
        // Remove a tabela 'adms_access_levels' do banco de dados
        $this->table('adms_users_departments')->drop()->save();
    }
}
