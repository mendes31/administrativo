<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\Validation\ValidationEmptyField;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\EvaluationModelsRepository;

/**
 * Controller para deletar Modelo de Avaliação
 *
 * Esta classe é responsável por deletar um modelo de avaliação do sistema.
 * Valida o token CSRF e o ID do registro antes de realizar a exclusão.
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class DeleteEvaluationModel
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

        if (!empty($this->dataForm['id'])) {
            $deleteEvaluationModel = new EvaluationModelsRepository();
            $deleteEvaluationModel->deleteModel($this->dataForm['id']);

            if ($deleteEvaluationModel->getResult()) {
                $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
                header("Location: $urlRedirect");
            } else {
                $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
                header("Location: $urlRedirect");
            }
        } else {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Erro: Modelo de avaliação não encontrado!</p>";
            $urlRedirect = $_ENV['URL_ADM'] . "list-evaluation-models";
            header("Location: $urlRedirect");
        }
    }
} 