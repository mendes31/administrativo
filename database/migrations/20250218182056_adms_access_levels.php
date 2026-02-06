<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsAccessLevels extends AbstractMigration
{
    /**
     * Cria a tabela AdmsAcessLevels.
     * 
     * Este método é executado durante a aplicação da migração para criar a tabeala `adms_acess_levels`no banco de dados.
     * A tabela é criada apenas se ela não existir, com as seguintes colunas:
     * - `name`: Nome do nível de acesso (não pode ser nulo)
     * - `order_levels`: Ordem do nível de acesso (não pode ser nulo)
     * `create_at`: Timestamp da criaç~çao do registro 
     * `update_at`: Timestamp da última atualização do registro
     * 
     * Referencia:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     */
    public function up(): void
    {
        // Verifica se a tabela 'adms_access_levels' não existe no banco de dados
        if(!$this->hasTable('adms_access_levels')){
            // Cria a tabela 'adms_access_levels'
            $table = $this->table('adms_access_levels');

            // Define as colunas da tabela
            $table->addColumn('name', 'string', ['null' => false])
                    ->addColumn('create_at', 'timestamp')
                    ->addColumn('update_at', 'timestamp')
                    // NOTA: Índice único não é criado aqui porque a coluna 'name' é VARCHAR(255) 
                    // com utf8mb4, o que resulta em 1020 bytes (255 * 4), excedendo o limite 
                    // de 767 bytes do MySQL para índices. A validação de unicidade é feita na aplicação PHP.
                    ->addIndex(['name'], ['unique' => false, 'name' => 'idx_name']) // Índice não-único para performance
                    ->create();

        }
    }

    /**
     * Reverter a criação da tabela AdmsAccessLevels
     * 
     * Este método é executado durante a reversão da migração para remover a tabela `adms_access_levels` do banco de dados.
     * 
     * @return void 
     * 
     */
    public function down(): void 
    {
        // Remove a tabela 'adms_access_levels' do banco de dados
        $this->table('adms_access_levels')->drop()->save();
    }

}
