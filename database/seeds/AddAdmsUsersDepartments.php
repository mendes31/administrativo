<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsUsersDepartments extends AbstractSeed
{
    
    /**
     * Vincular usuário ao departamento na tabela `adms_users_departments` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_users_departments` com registros iniciais de usuários e departamentos.
     * 
     * Primeiro veirifica se já existe o usuário e o departamento na tabela com base no id.
     * 
     * Se não existir, os dados serão inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {
        // Variável para receber os dados a serem inseridos
        $data = [];

        // Verificar se o usuário e o departamento já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_users_departments WHERE adms_user_id=:adms_user_id and adms_department_id=:adms_department_id', ['adms_user_id' => 1, 'adms_department_id' => 1])->fetch();

        // Se o usuário não existir, insere os dados no arrayl $data para em seguida cadastrar na tabela
        if (!$existingRecord) {

            // Criar o array com os dados do usuário
            $data[] = [
                'adms_user_id' => 1,
                'adms_department_id' => 1,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

        // Indicar em qual tabela deve salvar
        $adms_users_departments = $this->table('adms_users_departments');

        // Inserir os registros na tabela
        $adms_users_departments->insert($data)->save();
    }
}
