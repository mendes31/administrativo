<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar detalhes de uma resposta de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class ViewEvaluationAnswer
{
    /** @var array $data Dados que devem ser enviados para a VIEW */
    private array $data = [];

    /**
     * Exibe os detalhes de uma resposta de avaliação
     *
     * @param int|string|null $id ID da resposta
     * @return void
     */
    public function index($id = null): void
    {
        // Converter para int se for string
        if (is_string($id)) {
            $id = !empty($id) ? (int)$id : null;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID da resposta não informado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-answers');
            exit;
        }

        // Buscar resposta
        $answersRepo = new EvaluationAnswersRepository();
        $answer = $answersRepo->getAnswerById($id);

        if (!$answer) {
            $_SESSION['msg'] = 'Resposta não encontrada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-answers');
            exit;
        }

        $this->data['answer'] = $answer;

        // Buscar usuário
        if (!empty($answer['user_id'])) {
            $usersRepo = new UsersRepository();
            $user = $usersRepo->getUser($answer['user_id']);
            $this->data['user'] = $user;
        }

        // Buscar pergunta relacionada
        if (!empty($answer['evaluation_question_id'])) {
            $questionsRepo = new EvaluationQuestionsRepository();
            $question = $questionsRepo->getQuestion($answer['evaluation_question_id']);
            $this->data['question'] = $question;
        }

        // Buscar modelo relacionado
        if (!empty($answer['evaluation_model_id'])) {
            $modelsRepo = new EvaluationModelsRepository();
            $model = $modelsRepo->getModel($answer['evaluation_model_id']);
            $this->data['model'] = $model;
        }

        // Verificar se a resposta está correta (se houver resposta correta definida)
        if (!empty($question['resposta_correta']) && !empty($answer['resposta'])) {
            $this->data['correta'] = (trim(strtolower($answer['resposta'])) === trim(strtolower($question['resposta_correta'])));
        }

        // Definir status legível
        $statusLegiveis = [
            'respondido' => 'Respondido',
            'pendente' => 'Pendente',
            'corrigido' => 'Corrigido'
        ];
        $this->data['status_legivel'] = $statusLegiveis[$answer['status']] ?? $answer['status'];

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Visualizar Resposta de Avaliação',
            'menu' => 'list-evaluation-answers',
            'buttonPermission' => ['UpdateEvaluationAnswer', 'DeleteEvaluationAnswer', 'ListEvaluationAnswers'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService('adms/Views/evaluations/answers/view', $this->data);
        $loadView->loadView();
    }
}
