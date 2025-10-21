<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddStrategicPlanObservationsPages extends AbstractSeed
{
    /**
     * Cadastra páginas de observações de planos estratégicos na tabela `adms_pages` se ainda não existirem.
     *
     * Este método é executado para popular a tabela `adms_pages` com registros das páginas de observações.
     * Primeiro, verifica se já existe página na tabela com base no controller_url. 
     * Se a página não existir, os dados são inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {
        // Variável para receber os dados a serem inseridos
        $data = [];

        // Forçar charset/collation da sessão para evitar "Illegal mix of collations"
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $this->execute("SET collation_connection = 'utf8mb4_unicode_ci'");
        $this->execute("SET character_set_client = 'utf8mb4'");
        $this->execute("SET character_set_results = 'utf8mb4'");
        $this->execute("SET character_set_connection = 'utf8mb4'");

        // Buscar o ID do grupo "Planejamento Estratégico" (assumindo que existe)
        $groupResult = $this->query("SELECT id FROM adms_groups_pages WHERE name = 'Planejamento Estratégico'")->fetch();
        $groupId = $groupResult ? $groupResult['id'] : 1; // Fallback para grupo 1 se não encontrar

        // Páginas de observações de planos estratégicos
        $pages = [
            [
                'name' => 'Visualizar Observações do Plano',
                'controller' => 'ViewObservations',
                'controller_url' => 'view-strategic-plan-observations',
                'directory' => 'strategicPlans',
                'obs' => 'Visualização do histórico de observações de um plano estratégico',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ],
            [
                'name' => 'Adicionar Observação ao Plano',
                'controller' => 'AddObservation',
                'controller_url' => 'add-strategic-plan-observation',
                'directory' => 'strategicPlans',
                'obs' => 'Adicionar nova observação a um plano estratégico',
                'public_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $groupId
            ]
        ];

        // Percorrer o array com dados que devem ser validados antes de cadastrar
        foreach ($pages as $page) {
            // Verificar se o registro já existe no banco de dados
            $existingRecord = $this->query('SELECT id FROM adms_pages WHERE controller_url=:controller_url', 
                ['controller_url' => $page['controller_url']])->fetch();

            // Se o registro não existir, insere os dados na variável $data para em seguida cadastrar na tabela
            if (!$existingRecord) {
                // Criar o array com os dados da página
                $data[] = [
                    'name' => $page['name'],
                    'controller' => $page['controller'],
                    'controller_url' => $page['controller_url'],
                    'directory' => $page['directory'],
                    'obs' => $page['obs'],
                    'public_page' => $page['public_page'],
                    'page_status' => $page['page_status'],
                    'adms_packages_page_id' => $page['adms_packages_page_id'],
                    'adms_groups_page_id' => $page['adms_groups_page_id'],
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
            }
        }

        // Indicar em qual tabela deve salvar
        $adms_pages = $this->table('adms_pages');

        // Inserir os registros na tabela
        if (!empty($data)) {
            $adms_pages->insert($data)->save();
            echo "Páginas de observações de planos estratégicos cadastradas com sucesso!\n";
        } else {
            echo "Todas as páginas de observações já existem no banco de dados.\n";
        }
    }
}
