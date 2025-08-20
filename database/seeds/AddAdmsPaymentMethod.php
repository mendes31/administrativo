<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsPaymentMethod extends AbstractSeed
{
    /**
     * Cadastrar formas de pagamento na tabela `adms_payment_method` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_payment_method` com registros iniciais de formas de pagamentos.
     * 
     * Primeiro veirifica se já existe o formas de pagamento na tabela com base no name.
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
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Arquivo'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Arquivo',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Boleto'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Boleto',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Cartão'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Cartão',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

        // Verificar se o registro já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Cheque'])->fetch();

        // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {
 
            // Criar o array com os dados do usuário
            $data[] = [
                'name' => 'Cheque',
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }
 
        // Verificar se o registro já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Débito'])->fetch();
 
        // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {
 
            // Criar o array com os dados do usuário
            $data[] = [
                'name' => 'Débito',
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Dinheiro'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Dinheiro',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Manual'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manual',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Pix'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Pix',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }


       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Ted'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Ted',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_payment_method WHERE name=:name', ['name' => 'Tev'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Tev',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Indicar em qual tabela deve salvar
       $adms_departments = $this->table('adms_payment_method');

       // Inserir os registros na tabela
       $adms_departments->insert($data)->save();
    }
}
