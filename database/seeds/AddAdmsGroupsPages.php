<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsGroupsPages extends AbstractSeed
{
    /**
     * Cadastra grupos na tabela `adms_groups_pages` se ainda não existirem.
     *
     * Este método é executado para popular a tabela `adms_groups_pages` com registros iniciais dos grupos.
     * Primeiro, verifica se já existe grupo na tabela com base no name. 
     * Se o grupo não existir, os dados são inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {
        $data = [];

        $grupos = [
            ['name' => 'Dashboard', 'obs' => ''], // Nº 1
            ['name' => 'Usuários', 'obs' => ''], // Nº 2
            ['name' => 'Nível de Acesso', 'obs' => ''], // Nº 3
            ['name' => 'Pacote de Páginas', 'obs' => ''], // Nº 4
            ['name' => 'Grupo de Páginas', 'obs' => ''], // Nº 5
            ['name' => 'Páginas', 'obs' => ''], // Nº 6
            ['name' => 'Login', 'obs' => ''],    // Nº 7
            ['name' => 'Departamento', 'obs' => ''], // Nº 8
            ['name' => 'Erros', 'obs' => ''], // Nº 9
            ['name' => 'Cargo', 'obs' => ''], // Nº 10
            ['name' => 'Permissões', 'obs' => ''], // Nº 11
            ['name' => 'Bancos', 'obs' => ''], // Nº 12
            ['name' => 'Pagar', 'obs' => ''], // Nº 13
            ['name' => 'Receber', 'obs' => ''], // Nº 14
            ['name' => 'Centros de Custo', 'obs' => ''], // Nº 15
            ['name' => 'Plano de Contas', 'obs' => ''], // Nº 16
            ['name' => 'Frequências', 'obs' => ''], // Nº 17
            ['name' => 'Clientes', 'obs' => ''], // Nº 18
            ['name' => 'Fornecedores', 'obs' => ''], // Nº 19
            ['name' => 'Formas de Pagamento', 'obs' => ''], // Nº 20
            ['name' => 'Relatórios Financeiros', 'obs' => ''], // Nº 21
            ['name' => 'Movimentos', 'obs' => ''], // Nº 22
            ['name' => 'Documentos', 'obs' => ''], // Nº 23
            ['name' => 'Treinamentos', 'obs' => ''], // Nº 24
            ['name' => 'Avaliações', 'obs' => ''], // Nº 25
            ['name' => 'Configurações', 'obs' => 'Configurações gerais do sistema'], // Nº 26
            ['name' => 'Administração de Senhas', 'obs' => 'Administração de Senhas'], // Nº 27
            ['name' => 'Logs', 'obs' => 'Páginas de auditoria e logs do sistema'], // Nº 28
            ['name' => 'Planejamento Estratégico', 'obs' => 'Gestão de planos e indicadores estratégicos'], // Nº 29
            ['name' => 'Informativos', 'obs' => 'Páginas de informativos'], // Nº 30
            ['name' => 'LGPD', 'obs' => 'Gestão da LGPD e privacidade'], // Nº 31
        ];

        foreach ($grupos as $grupo) {
            $existingRecord = $this->query('SELECT id FROM adms_groups_pages WHERE name=:name', ['name' => $grupo['name']])->fetch();
            if (!$existingRecord) {
                $data[] = [
                    'name' => $grupo['name'],
                    'obs' => $grupo['obs'],
                    'created_at' => date("Y-m-d H:i:s"),
                ];
            }
        }

        if (!empty($data)) {
            $adms_groups_pages = $this->table('adms_groups_pages');
            $adms_groups_pages->insert($data)->save();
        }
    }
}
