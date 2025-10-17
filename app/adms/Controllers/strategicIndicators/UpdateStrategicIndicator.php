<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class UpdateStrategicIndicator
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicIndicatorsRepository();
    }

    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do indicador não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $_SESSION['msg'] = "Indicador estratégico atualizado com sucesso!";
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['msg'] = "Erro ao atualizar indicador estratégico!";
                $_SESSION['msg_type'] = "danger";
            }
            
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        $this->viewUpdate($id);
    }

    private function viewUpdate(int $id): void
    {
        // Buscar o indicador estratégico
        $indicator = $this->repository->getById($id);
        
        if (!$indicator) {
            $_SESSION['msg'] = "Indicador estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        // Elementos de página
        $pageElements = [
            'title_head' => 'Atualizar Indicador Estratégico',
            'menu' => 'strategic-indicators-list',
            'buttonPermission' => ['StrategicIndicatorsList'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['indicator'] = $indicator;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicIndicators/update-strategic-indicator", $this->data);
        $loadView->loadView();
    }
}



