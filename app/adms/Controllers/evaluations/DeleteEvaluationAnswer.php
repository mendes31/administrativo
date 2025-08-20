<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAnswersRepository;

/**
 * Controller para deletar respostas de avaliação.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class DeleteEvaluationAnswer
{
    /** @var int|string|null $id Recebe o id do registro que deve ser excluído do banco de dados */
    private int|string|null $id;

    /** @var bool $result Recebe true quando executar o processo com sucesso e false quando houver erro */
    private bool $result = false;

    /**
     * Recebe os dados do formulário, instancia a classe "EvaluationAnswersRepository" e chama o método deleteEvaluationAnswer
     * @param int|string|null $id Recebe o ID do registro que deve ser excluído
     * @return void
     */
    public function index(int|string|null $id = null): void
    {
        if (!empty($id)) {
            $this->id = (int) $id;
            $deleteEvaluationAnswer = new EvaluationAnswersRepository();
            $deleteEvaluationAnswer->delete($this->id);
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Resposta de avaliação não encontrada!</p>";
        }

        $urlRedirect = getenv('URL_ADM') . "list-evaluation-answers/index";
        header("Location: $urlRedirect");
    }
} 