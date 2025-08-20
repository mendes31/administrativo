<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Models\Repository\LgpdDataGroupsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela criação de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdCreate
{
    protected array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Mapear campos do formulário para os campos da tabela
            $createData = [
                'titulo' => $_POST['titulo'],
                'departamento_id' => $_POST['departamento_id'],
                'responsavel_id' => $_POST['responsavel_id'],
                'ropa_id' => $_POST['ropa_id'] ?? null,
                'data_inicio' => $_POST['data_inicio'],
                'data_conclusao' => $_POST['data_fim_prevista'] ?? null, // Mapear data_fim_prevista para data_conclusao
                'nivel_risco' => $_POST['nivel_risco'],
                'descricao' => $_POST['objetivo'] . "\n\nEscopo: " . $_POST['escopo'] . "\n\nMetodologia: " . ($_POST['metodologia'] ?? ''),
                'observacoes' => "Riscos: " . ($_POST['riscos_identificados'] ?? '') . "\n\nMedidas: " . ($_POST['medidas_mitigacao'] ?? '') . "\n\nConclusões: " . ($_POST['conclusoes'] ?? '') . "\n\nRecomendações: " . ($_POST['recomendacoes'] ?? '') . "\n\nObservações: " . ($_POST['observacoes'] ?? '')
            ];
            
            // Adicionar grupos de dados se selecionados
            if (!empty($_POST['data_groups']) && is_array($_POST['data_groups'])) {
                $createData['data_groups'] = $_POST['data_groups'];
            }
            
            $repo = new LgpdAipdRepository();
            $result = $repo->create($createData);
            
            if ($result) {
                $_SESSION['success'] = "AIPD cadastrada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não cadastrada!";
            }
        }

        // Carregar dados para os selects
        $departmentsRepo = new DepartmentsRepository();
        $ropaRepo = new LgpdRopaRepository();
        $dataGroupsRepo = new LgpdDataGroupsRepository();
        $usersRepo = new UsersRepository();
        
        $this->data['departamentos'] = $departmentsRepo->getAllDepartmentsSelect();
        $this->data['ropas'] = $ropaRepo->getAllRopaForSelect();
        $this->data['data_groups'] = $dataGroupsRepo->getAllDataGroupsForSelect();
        $this->data['usuarios'] = $usersRepo->getAllUsersForSelect();

        $pageElements = [
            'title_head' => 'Cadastrar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['CreateLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/create", $this->data);
        $loadView->loadView();
    }
}
