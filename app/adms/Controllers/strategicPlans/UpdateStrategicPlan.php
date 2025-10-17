<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class UpdateStrategicPlan
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicPlansRepository();
    }

    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do plano não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $_SESSION['msg'] = "Plano estratégico atualizado com sucesso!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Erro ao atualizar plano estratégico!";
                $_SESSION['msg_type'] = "danger";
            }
            
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        $this->viewUpdate($id);
    }

    private function viewUpdate(int $id): void
    {
        // Buscar o plano estratégico
        $plan = $this->repository->getById($id);
        
        if (!$plan) {
            $_SESSION['msg'] = "Plano estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        // Elementos de página
        $pageElements = [
            'title_head' => 'Atualizar Plano Estratégico',
            'menu' => 'list-strategic-plans',
            'buttonPermission' => ['ListStrategicPlans'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['plan'] = $plan;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicPlans/update-strategic-plan", $this->data);
        $loadView->loadView();
    }
}



