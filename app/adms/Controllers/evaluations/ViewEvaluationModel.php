<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar Modelo de Avaliação
 *
 * Esta classe é responsável por exibir os detalhes de um modelo de avaliação específico.
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ViewEvaluationModel
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int|string|null $id Recebe o ID do registro */
    private int|string|null $id;

    /**
     * Instanciar a classe responsável em carregar a view e enviar os dados para a view.
     *
     * @param int|string|null $id Recebe o ID do registro
     * @return void
     */
    public function index(int|string|null $id = null): void
    {
        $this->id = (int) $id;

        $viewEvaluationModel = new EvaluationModelsRepository();
        $this->data['form'] = $viewEvaluationModel->getModel($this->id);

        if ($this->data['form']) {
            // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
            $pageElements = [
                'title_head' => 'Visualizar Modelo de Avaliação',
                'menu' => 'view-evaluation-model',
                'buttonPermission' => ['ViewEvaluationModel', 'UpdateEvaluationModel', 'DeleteEvaluationModel'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/evaluations/models/view", $this->data);
            $loadView->loadView();
        } else {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Erro: Modelo de avaliação não encontrado!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
            header("Location: $urlRedirect");
        }
    }
} 