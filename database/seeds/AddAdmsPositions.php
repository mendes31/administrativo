<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsPositions extends AbstractSeed
{
    /**
     * Cadastrar cargo na tabela `adms_positions` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_positions` com registros iniciais de cargos.
     * 
     * Primeiro veirifica se já existe o cargo na tabela com base no name.
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
       $existingRecord = $this->query('SELECT id FROM adms_positions WHERE name=:name', ['name' => 'Geral'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Geral',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Indicar em qual tabela deve salvar
       $adms_departments = $this->table('adms_positions');

       // Inserir os registros na tabela
       $adms_departments->insert($data)->save();
    }
}
