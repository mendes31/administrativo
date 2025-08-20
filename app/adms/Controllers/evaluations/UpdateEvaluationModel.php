<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar Modelo de Avaliação
 *
 * Esta classe é responsável por atualizar um modelo de avaliação existente no sistema.
 * Valida os dados enviados pelo formulário e atualiza no banco de dados.
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class UpdateEvaluationModel
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|null $dataForm Recebe os dados do formulário */
    private array|null $dataForm;

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

        $this->dataForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->dataForm['SendEditEvaluationModel'])) {
            unset($this->dataForm['SendEditEvaluationModel']);
            $updateEvaluationModel = new EvaluationModelsRepository();
            $updateEvaluationModel->updateModel($this->id, $this->dataForm);

            if ($updateEvaluationModel->getResult()) {
                $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
                header("Location: $urlRedirect");
            } else {
                $this->data['form'] = $this->dataForm;
            }
        } else {
            $viewEvaluationModel = new EvaluationModelsRepository();
            $this->data['form'] = $viewEvaluationModel->getModel($this->id);
        }

        $this->loadFormData();

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Editar Modelo de Avaliação',
            'menu' => 'update-evaluation-model',
            'buttonPermission' => ['UpdateEvaluationModel'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/evaluations/models/update", $this->data);
        $loadView->loadView();
    }

    /**
     * Carregar dados para o formulário.
     *
     * @return void
     */
    private function loadFormData(): void
    {
        // Carregar lista de treinamentos para o select
        $trainingsRepository = new TrainingsRepository();
        $this->data['trainings'] = $trainingsRepository->getAllTrainings();
    }
} 