<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * @method \Phinx\Db\Table table(string $tableName, array $options = [])
 * @method \Phinx\Db\Adapter\AdapterInterface getAdapter()
 */
class AddAdmsUsers extends AbstractSeed
{
    /**
     * Cadastra usuários na tabela `adms_users` se ainda não existirem.
     *
     * Este método é executado para popular a tabela `adms_users` com registros iniciais de usuários.
     * Primeiro, verifica se cada usuário já existe na tabela com base no username. Se o usuário não existir,
     * os dados são inseridos na tabela. As senhas são armazenadas usando `password_hash` para garantir a segurança.
     * 
     * @return void
     */
    public function run(): void
    {
        // variável para receber os dados
        $data = [];

        // Verificar se o registro já existe no banco de dados
        // Usar getAdapter()->query() para executar queries SQL diretas
        $adapter = $this->getAdapter();
        $existingRecord = $adapter->query(
            "SELECT id FROM adms_users WHERE username = 'manager'"
        )->fetch();

        // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {

            // Criar o array com os dados do usuário
            $data[] = [
                'name' => 'Manager',
                'email' => 'manager@tiaraju.com.br',
                'username' => 'manager',
                'user_department_id' => 1,
                'user_position_id' => 1,
                'password' => password_hash('B1admin*', PASSWORD_DEFAULT),
                'super_usuario' => 1,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }
        
        // Indicar em qual tabela deve salvar
        $adms_users = $this->table('adms_users');

        // Inserir os registros na tabela apenas se houver dados
        if (!empty($data)) {
            $adms_users->insert($data)->save();
        }
    }
}
