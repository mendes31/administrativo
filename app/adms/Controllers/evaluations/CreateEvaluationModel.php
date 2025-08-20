<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationEmptyField;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar Modelo de Avaliação
 *
 * Esta classe é responsável por criar um novo modelo de avaliação no sistema.
 * Valida os dados enviados pelo formulário e salva no banco de dados.
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class CreateEvaluationModel
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|null $dataForm Recebe os dados do formulário */
    private array|null $dataForm;

    /**
     * Instanciar a classe responsável em carregar a view e enviar os dados para a view.
     *
     * @return void
     */
    public function index(): void
    {
        $this->dataForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->dataForm['SendAddEvaluationModel'])) {
            unset($this->dataForm['SendAddEvaluationModel']);
            $createEvaluationModel = new EvaluationModelsRepository();
            $createEvaluationModel->createModel($this->dataForm);

            if ($createEvaluationModel->getResult()) {
                $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
                header("Location: $urlRedirect");
            } else {
                $this->data['form'] = $this->dataForm;
            }
        }

        $this->loadFormData();

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Criar Modelo de Avaliação',
            'menu' => 'create-evaluation-model',
            'buttonPermission' => ['CreateEvaluationModel'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/evaluations/models/create", $this->data);
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

        // Se não houver dados do formulário, inicializar com valores padrão
        if (empty($this->dataForm)) {
            $this->data['form'] = [
                'training_id' => '',
                'titulo' => '',
                'descricao' => '',
                'ativo' => 1
            ];
        }
    }
} 