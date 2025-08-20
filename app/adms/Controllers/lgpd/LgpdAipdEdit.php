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
 * Controller responsável pela edição de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdEdit
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $aipd = $repo->getAipdById($id);

        if (!$aipd) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        // Mapear campos da tabela para os campos esperados pela view
        $this->data['form'] = [
            'nome' => $aipd['titulo'],
            'departamento_id' => $aipd['departamento_id'],
            'responsavel_id' => $aipd['responsavel_id'],
            'status' => $aipd['status'],
            'data_inicio' => $aipd['data_inicio'],
            'data_fim' => $aipd['data_conclusao'], // Mapear data_conclusao para data_fim
            'descricao' => $aipd['descricao'],
            'objetivo' => $aipd['observacoes'], // Usar observacoes como objetivo
            'escopo' => $aipd['descricao'], // Usar descricao como escopo
            'metodologia' => $aipd['observacoes'], // Usar observacoes como metodologia
            'observacoes' => $aipd['observacoes']
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Mapear campos do formulário para os campos da tabela
            $updateData = [
                'titulo' => $_POST['nome'],
                'departamento_id' => $_POST['departamento_id'],
                'responsavel_id' => $_POST['responsavel_id'],
                'status' => $_POST['status'],
                'data_inicio' => $_POST['data_inicio'],
                'data_conclusao' => $_POST['data_fim'], // Mapear data_fim para data_conclusao
                'descricao' => $_POST['descricao'],
                'observacoes' => $_POST['observacoes']
            ];
            
            $result = $repo->update($id, $updateData);
            
            if ($result) {
                $_SESSION['success'] = "AIPD editada com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
                exit;
            } else {
                $this->data['errors'][] = "AIPD não editada!";
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
            'title_head' => 'Editar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['EditLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/edit", $this->data);
        $loadView->loadView();
    }
}
