<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsFrequency extends AbstractSeed
{
    /**
     * Cadastrar frequencia na tabela `adms_frequency` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_frequency` com registros iniciais de frequencias.
     * 
     * Primeiro veirifica se já existe o frequencia na tabela com base no name.
     * 
     * Se não existir, os dados serão inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {
       // Variável para receber os dados a serem inseridos
       $data = [];

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_frequency WHERE name=:name', ['name' => 'Uma vez'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Uma vez',
                'days' => 0,
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_frequency WHERE name=:name', ['name' => 'Diária'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Diária',
               'days' => 1,
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_frequency WHERE name=:name', ['name' => 'Semanal'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Semanal',
               'days' => 7,
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

        // Verificar se o registro já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_frequency WHERE name=:name', ['name' => 'Mensal'])->fetch();

        // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {
 
            // Criar o array com os dados do usuário
            $data[] = [
                'name' => 'Mensal',
                'days' => 30,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }


       // Indicar em qual tabela deve salvar
       $adms_departments = $this->table('adms_frequency');

       // Inserir os registros na tabela
       $adms_departments->insert($data)->save();
    }
}
