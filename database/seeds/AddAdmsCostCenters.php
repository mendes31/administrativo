<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsCostCenters extends AbstractSeed
{
    /**
     * Cadastrar centro de custo na tabela `adms_cost_center` se ainda não existir.
     * 
     * Este método é executado para popular a tabela `adms_cost_center` com registros iniciais de centros de custo.
     * 
     * Primeiro veirifica se já existe o centro de custo na tabela com base no name.
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
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Afra'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Afra',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Água '])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Água ',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Analitico'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Analitico',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Ar (Assuntos Regulatorios)'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Ar (Assuntos Regulatorios)',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Assessoria/Consultoria'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Assessoria/Consultoria',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Auxilio Educação'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Auxilio Educação',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Biotics'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Biotics',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Cartao Corporativo'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Cartao Corporativo',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Cartório/Registro De Imoveis'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Cartório/Registro De Imoveis',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Coleta De Residuos'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Coleta De Residuos',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Comercial'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Comercial',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Comissões'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Comissões',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Confraternização/Eventos'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Confraternização/Eventos',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Consorcio'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Consorcio',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Contador'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Contador',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Contribuicao Social'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Contribuicao Social',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'CQ - Controle de Qualidade'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'CQ - Controle de Qualidade',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Cursos E Treinamento De Pessoal'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Cursos E Treinamento De Pessoal',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Depesas Postais'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Depesas Postais',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Despesas Com Aluguéis E Condomínios'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Despesas Com Aluguéis E Condomínios',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Despesas Com Veículos/Frota'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Despesas Com Veículos/Frota',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Despesas Diversas'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Despesas Diversas',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Despesas Gerais E Administrativas'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Despesas Gerais E Administrativas',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Despesas Viagem'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Despesas Viagem',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Detetização'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Detetização',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Difal'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Difal',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Doações, Contribuições E Patrocínios'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Doações, Contribuições E Patrocínios',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Emprestimos'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Emprestimos',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Emprestimos Para Socios'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Emprestimos Para Socios',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Energia Elétrica'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Energia Elétrica',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Epis'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Epis',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Feiras E Eventos'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Feiras E Eventos',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Fgts'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Fgts',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Fretes Compra E Venda'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Fretes Compra E Venda',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Funcionarios'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Funcionarios',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Gerador'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Gerador',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'GQ - Garantia da Qualidade'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'GQ - Garantia da Qualidade',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Icms'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Icms',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Imoveis Balneario'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Imoveis Balneario',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Imoveis Geral'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Imoveis Geral',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Imp Retidos Terc.Pis/Confis/Csll'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Imp Retidos Terc.Pis/Confis/Csll',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Impressão'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Impressão',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Informatica'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Informatica',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Inss'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Inss',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Internet'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Internet',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Iptu/Itbi'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Iptu/Itbi',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Irpj'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Irpj',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Irrpf/Irf'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Irrpf/Irf',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Iss'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Iss',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Jornal'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Jornal',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Juridico'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Juridico',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Limpeza'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Limpeza',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutenção Chácara'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutenção Chácara',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutençao Diversas'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutençao Diversas',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutenção E Conservação Prédio'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutenção E Conservação Prédio',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutenção Equipamentos E Maquinas'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutenção Equipamentos E Maquinas',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutencao Patio'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutencao Patio',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Manutenção Produção'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Manutenção Produção',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Material Acondicionamento e Embalagem'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Material Acondicionamento e Embalagem',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Material Consumo'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Material Consumo',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Material Expediente'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Material Expediente',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Matéria-Prima'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Matéria-Prima',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Medicina Do Trabalho'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Medicina Do Trabalho',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Mototaxi/Taxi'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Mototaxi/Taxi',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'P&D - Pesquisa e Desenvolvimento'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'P&D - Pesquisa e Desenvolvimento',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Pis/Cofins'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Pis/Cofins',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Plano De Saúde'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Plano De Saúde',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Pro Labore'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Pro Labore',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Propaganda E Publicidade'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Propaganda E Publicidade',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Recursos Humanos'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Recursos Humanos',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Refeitorio'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Refeitorio',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Segurança Do Trabalho'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Segurança Do Trabalho',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Seguros'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Seguros',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Serasa'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Serasa',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Serviços De Vigilância/Segurança'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Serviços De Vigilância/Segurança',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Sindicato'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Sindicato',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Sistemas (Sap)'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Sistemas (Sap)',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }
       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Tarifas Bancarias(Tarifas,Doc/Ted,Taxas)'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Tarifas Bancarias(Tarifas,Doc/Ted,Taxas)',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Tarifas Cobrança (Alt/Bai/Dev/Sust,Cartorio, Titulo)'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Tarifas Cobrança (Alt/Bai/Dev/Sust,Cartorio, Titulo)',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Taxas E Registros'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Taxas E Registros',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Telefone Fixo'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Telefone Fixo',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Telefone Móvel'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Telefone Móvel',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Verificar se o registro já existe no banco de dados
       $existingRecord = $this->query('SELECT id FROM adms_cost_center WHERE name=:name', ['name' => 'Vale Transporte'])->fetch();

       // Se o registro não existir, insere os dados na veriável $data para em seguida cadastrar na tabela
       if (!$existingRecord) {

           // Criar o array com os dados do usuário
           $data[] = [
               'name' => 'Vale Transporte',
               'created_at' => date("Y-m-d H:i:s"),
           ];
       }

       // Indicar em qual tabela deve salvar
       $adms_cost_center = $this->table('adms_cost_center');

       // Inserir os registros na tabela
       $adms_cost_center->insert($data)->save();
    }
}