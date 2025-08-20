<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsLogAcessos extends AbstractMigration
{
    /**
     * Cria a tabela adms_log_acessos.
     *
     * Este método é executado durante a aplicação da migração para criar a tabela `adms_log_acessos` no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `usuario_id`: ID do usuário que fez o acesso (não pode ser nulo)
     * - `tipo_acesso`: Tipo de acesso (LOGIN ou LOGOUT) (não pode ser nulo)
     * - `ip`: Endereço IP do usuário (não pode ser nulo)
     * - `user_agent`: User agent do navegador (pode ser nulo)
     * - `data_acesso`: Data e hora do acesso (não pode ser nulo)
     * - `detalhes`: Detalhes adicionais do acesso (pode ser nulo)
     * - `criado_por`: ID do usuário que criou o registro (pode ser nulo)
     * - `created_at`: Timestamp da criação do registro
     *
     * @return void
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_log_acessos' não existe no banco de dados
        if (!$this->hasTable('adms_log_acessos')) {

            // Cria a tabela 'adms_log_acessos'
            $table = $this->table('adms_log_acessos');

            // Define as colunas da tabela
            $table->addColumn('usuario_id', 'integer', ['null' => false])
                ->addColumn('tipo_acesso', 'string', ['limit' => 20, 'null' => false, 'comment' => 'LOGIN ou LOGOUT'])
                ->addColumn('ip', 'string', ['limit' => 45, 'null' => false])
                ->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('data_acesso', 'datetime', ['null' => false])
                ->addColumn('detalhes', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('criado_por', 'integer', ['null' => true])
                ->addColumn('created_at', 'timestamp')
                ->create();
        }
    }

    /**
     * Reverte a criação da tabela adms_log_acessos.
     *
     * Este método é executado durante a reversão da migração para remover a tabela `adms_log_acessos` do banco de dados.
     * 
     * @return void
     */
    public function down(): void
    {
        // Apagar a tabela adms_log_acessos
        $this->table('adms_log_acessos')->drop()->save();
    }
} 