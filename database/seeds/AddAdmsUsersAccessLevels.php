<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsUsersAccessLevels extends AbstractSeed
{
  
    public function run(): void
    {

        // Variável para receber os dados a serem inseridos
        $data = [];

        // Verificar se o usuário e o nivel de acesso já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_users_access_levels WHERE adms_user_id=:adms_user_id AND adms_access_level_id=:adms_access_level_id', ['adms_user_id' => 1, 'adms_access_level_id' => 1])->fetch();
        

        // Se o usuário não existir, insere os dados no arrayl $data para em seguida cadastrar na tabela
        if (!$existingRecord) {

            // Criar o array com os dados do usuário
            $data[] = [
                'adms_user_id' => 1,
                'adms_access_level_id' => 1,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

        // Indicar em qual tabela deve salvar
        $adms_users_access_levels = $this->table('adms_users_access_levels');

        // Inserir os registros na tabela
        $adms_users_access_levels->insert($data)->save();
    }
}
