<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\StrategicIndicatorsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar Indicador Estratégico
 * @package App\adms\Controllers\strategicIndicators
 */
class ViewStrategicIndicator
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array $data = [];

    /**
     * Visualizar um Indicador Estratégico específico.
     *
     * @param string|int|null $id ID do indicador estratégico
     * @return void
     */
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

        // Instancia o repositório
        $indicatorsRepo = new StrategicIndicatorsRepository();

        // Busca o indicador estratégico pelo ID
        $this->data['indicator'] = $indicatorsRepo->getById($id);

        // Verifica se o indicador existe
        if (!$this->data['indicator']) {
            $_SESSION['msg'] = "Indicador Estratégico não encontrado!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        // Define dados da página (título, menu ativo, permissões de botões)
        $pageElements = [
            'title_head' => 'Visualizar Indicador Estratégico',
            'menu' => 'strategic-indicators-list',
            'buttonPermission' => ['StrategicIndicatorsEdit', 'StrategicIndicatorsList'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carrega a VIEW
        $loadView = new LoadViewService("adms/Views/strategicIndicators/view-strategic-indicator", $this->data);
        $loadView->loadView();
    }
}
