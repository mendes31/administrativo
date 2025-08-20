<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsBankAccounts extends AbstractSeed
{
    /**
     * Cadastrar banco na tabela `adms_bank_accounts` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_bank_accounts` com registros iniciais de bancos.
     * 
     * Primeiro veirifica se já existe o banco na tabela com base no name.
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
       $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Banco do Brasil'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'bank_name' => 'Banco do Brasil',
               'bank' => 'Banco do Brasil',
               'account' => 1,
               'agency' => 1,
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Bradesco'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
                'bank_name' => 'Bradesco',
                'bank' => 'Bradesco',
                'account' => 2,
                'agency' => 2,
                'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Caixa'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
                'bank_name' => 'Caixa',
                'bank' => 'Caixa',
                'account' => 3,
                'agency' => 3,
                'created_at' => date("Y-m-d H:i:s"),
           ];
       }

        // Verificar se o registro já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Caixa Economica'])->fetch();

        // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {
 
            // Criar o array com os dados do usuário
            $data[] = [
                'bank_name' => 'Caixa Economica',
                'bank' => 'Caixa Economica',
                'account' => 4,
                'agency' => 4,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }
 
        // Verificar se o registro já existe no banco de dados
        $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Sicoob'])->fetch();
 
        // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
        if (!$existingRecord) {
 
            // Criar o array com os dados do usuário
            $data[] = [
                'bank_name' => 'Sicoob',
                'bank' => 'Sicoob',
                'account' => 5,
                'agency' => 5,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Sicredi'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
                'bank_name' => 'Sicredi',
                'bank' => 'Sicredi',
                'account' => '6',
                'agency' => '6',
                'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_bank_accounts WHERE bank_name = :bank_name', ['bank_name' => 'Unicred'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
                'bank_name' => 'Unicred',
                'bank' => 'Unicred',
                'account' => 7,
                'agency' => 7,
                'created_at' => date("Y-m-d H:i:s"),
           ];
       }

    

       // Indicar em qual tabela deve salvar
       $adms_departments = $this->table('adms_bank_accounts');

       // Inserir os registros na tabela
       $adms_departments->insert($data)->save();
    }
}
