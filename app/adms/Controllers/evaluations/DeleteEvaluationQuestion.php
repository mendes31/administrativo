<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para deletar pergunta de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class DeleteEvaluationQuestion
{
    /**
     * Deleta uma pergunta de avaliação
     *
     * @return void
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;
        }

        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_delete_evaluation_question', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            $questionId = (int)($_POST['id'] ?? 0);

            if (!$questionId) {
                throw new \Exception('ID da pergunta não informado!');
            }

            // Buscar pergunta
            $questionsRepo = new EvaluationQuestionsRepository();
            $question = $questionsRepo->getQuestion($questionId);

            if (!$question) {
                throw new \Exception('Pergunta não encontrada!');
            }

            // Deletar pergunta
            $resultado = $questionsRepo->deleteQuestion($questionId);

            if (!$resultado) {
                throw new \Exception('Erro ao deletar pergunta!');
            }

            // LOG
            EvaluationLogService::logQuestionDeleted(
                $questionId,
                $question,
                $_SESSION['user_id'] ?? 1
            );

            $_SESSION['msg'] = 'Pergunta deletada com sucesso!';
            $_SESSION['msg_type'] = 'success';

        } catch (\Exception $e) {
            EvaluationLogService::logError('deletar_pergunta', $e, ['question_id' => $questionId ?? 0]);
            
            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
        exit;
    }
}

