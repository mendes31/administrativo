<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsEmailConfig extends AbstractMigration
{
    /**
     * Cria a tabela AdmsEmailConfig.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_email_config` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `host`: Servidor SMTP (não pode ser nulo)
     * - `username`: Usuário/e-mail SMTP (não pode ser nulo)
     * - `password`: Senha SMTP (não pode ser nulo)
     * - `port`: Porta do servidor SMTP (padrão: 587)
     * - `encryption`: Tipo de criptografia (padrão: TLS)
     * - `from_email`: E-mail remetente (não pode ser nulo)
     * - `from_name`: Nome remetente (não pode ser nulo)
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
        // Verifica se a tabela 'adms_email_config' não existe no banco de dados
        if (!$this->hasTable('adms_email_config')) {

            // Cria a tabela 'adms_email_config'
            $table = $this->table('adms_email_config');

            // Define as colunas da tabela
            $table->addColumn('host', 'string', ['null' => false, 'limit' => 255])
                  ->addColumn('username', 'string', ['null' => false, 'limit' => 255])
                  ->addColumn('password', 'string', ['null' => false, 'limit' => 255])
                  ->addColumn('port', 'integer', ['null' => false, 'default' => 587])
                  ->addColumn('encryption', 'string', ['null' => false, 'limit' => 10, 'default' => 'TLS'])
                  ->addColumn('from_email', 'string', ['null' => false, 'limit' => 255])
                  ->addColumn('from_name', 'string', ['null' => false, 'limit' => 255])
                  ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('updated_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                  ->create();
        }
    }

    /**
     * Reverte a criação da tabela AdmsEmailConfig.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_email_config` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Apagar a tabela adms_email_config
        $this->table('adms_email_config')->drop()->save();
    }
} 