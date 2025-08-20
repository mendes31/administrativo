<?php

namespace App\adms\Controllers\costCenter;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar um Centro de Custo
 *
 * Esta classe é responsável por exibir as informações detalhadas de um Centro de Custo específico. Ela recupera os dados
 * do Centro de Custo a partir do repositório, valida se o Centro de Custo existe e carrega a visualização apropriada. Se o Centro de Custo
 * não for encontrado, uma mensagem de erro é exibida e o Centro de Custo é redirecionado para a página de lista.
 *
 * @package App\adms\Controllers\costCenter
 * @author Rafael Mendes de Oliveira
 */
class ViewCostCenter
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Recuperar os detalhes do Centro de Custo.
     *
     * Este método gerencia a recuperação e exibição dos detalhes de um Centro de Custo específico. Ele valida o ID fornecido,
     * recupera os dados do Centro de Custo do repositório e carrega a visualização. Se o Centro de Custo não for encontrado, registra
     * um erro, exibe uma mensagem e redireciona para a página de lista de Centro de Custo.
     *
     * @param int|string $id ID do Centro de Custo a ser visualizado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Validar se o ID é um valor inteiro
        if (!(int) $id) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Centro de Custo não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Centro de Custo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-cost-centers");
            return;
        }

        // Instanciar o Repository para recuperar o registro do banco de dados
        $viewCostCenters = new CostCentersRepository();
        $this->data['costCenter'] = $viewCostCenters->getCostCenter((int) $id);

        // Verificar se encontrou o registro no banco de dados
        if (!$this->data['costCenter']) {
            // Registrar o erro e redirecionar
            GenerateLog::generateLog("error", "Centro de Custo não encontrado", ['id' => (int) $id]);
            $_SESSION['error'] = "Centro de Custo não encontrado!";
            header("Location: {$_ENV['URL_ADM']}list-cost-centers");
            return;
        }

        // Registrar a visualização do Centro de Custo
        GenerateLog::generateLog("info", "Visualizado o Centro de Custo.", ['id' => (int) $id]);

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Visualizar Centro de Custo',
            'menu' => 'list-cost-centers',
            'buttonPermission' => ['ListCostCenters', 'UpdateCostCenter', 'DeleteCostCenter'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/costCenter/view", $this->data);
        $loadView->loadView();
    }
}
