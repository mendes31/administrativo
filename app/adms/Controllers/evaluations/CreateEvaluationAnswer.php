<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para criar respostas de avaliação.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class CreateEvaluationAnswer
{
    /** @var array|string|null $data Dados enviados para a VIEW */
    private array|string|null $data = null;
    /** @var array|null $dataForm Dados do formulário */
    private array|null $dataForm = null;

    public function index(): void
    {
        $this->dataForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->dataForm['SendCreateEvaluationAnswer'])) {
            unset($this->dataForm['SendCreateEvaluationAnswer']);

            // Validar CSRF token
            if (!CSRFHelper::validateCSRFToken('form_create_evaluation_answer', $this->dataForm['csrf_token'])) {
                $this->data['error'] = "Token de segurança inválido. Tente novamente.";
            } else {
                // Validar dados
                $this->validateData();

                // Se não houver erro, salvar no banco
                if (empty($this->data['error'])) {
                    $this->createAnswer();
                }
            }
        }

        $this->loadFormData();
        $this->viewCreateEvaluationAnswer();
    }

    /**
     * Carregar dados para o formulário
     */
    private function loadFormData(): void
    {
        $usersRepository = new UsersRepository();
        $modelsRepository = new EvaluationModelsRepository();
        $questionsRepository = new EvaluationQuestionsRepository();

        $this->data['users'] = $usersRepository->getAllUsers();
        $this->data['models'] = $modelsRepository->getAllModels();
        $this->data['questions'] = $questionsRepository->getAllQuestions();
    }

    /**
     * Carregar a VIEW
     */
    private function viewCreateEvaluationAnswer(): void
    {
        $pageElements = [
            'title_head' => 'Criar Resposta de Avaliação',
            'menu' => 'create-evaluation-answer',
            'buttonPermission' => ['ListEvaluationAnswers'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Gerar novo token CSRF
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_create_evaluation_answer');

        $loadView = new LoadViewService('adms/Views/evaluations/answers/create', $this->data);
        $loadView->loadView();
    }

    /**
     * Validar os dados do formulário.
     *
     * @return void
     */
    private function validateData(): void
    {
        // Validar usuário
        if (empty($this->dataForm['usuario_id'])) {
            $this->data['error'] = "Erro: Selecione um usuário!";
            return;
        }

        // Validar modelo
        if (empty($this->dataForm['evaluation_model_id'])) {
            $this->data['error'] = "Erro: Selecione um modelo de avaliação!";
            return;
        }

        // Validar pergunta
        if (empty($this->dataForm['evaluation_question_id'])) {
            $this->data['error'] = "Erro: Selecione uma pergunta!";
            return;
        }

        // Validar resposta
        if (empty($this->dataForm['resposta'])) {
            $this->data['error'] = "Erro: Digite a resposta!";
            return;
        }

        if (strlen($this->dataForm['resposta']) < 3) {
            $this->data['error'] = "Erro: A resposta deve ter pelo menos 3 caracteres!";
            return;
        }

        // Validar pontuação
        if (!empty($this->dataForm['pontuacao'])) {
            if (!is_numeric($this->dataForm['pontuacao']) || $this->dataForm['pontuacao'] < 0 || $this->dataForm['pontuacao'] > 10) {
                $this->data['error'] = "Erro: A pontuação deve ser um número entre 0 e 10!";
                return;
            }
        }

        // Validar status
        $statusValidos = ['ativo', 'inativo'];
        if (!empty($this->dataForm['status']) && !in_array($this->dataForm['status'], $statusValidos)) {
            $this->data['error'] = "Erro: Status inválido!";
            return;
        }

        // Se chegou até aqui, não há erros
        $this->data['error'] = null;
    }

    /**
     * Criar a resposta no banco de dados.
     *
     * @return void
     */
    private function createAnswer(): void
    {
        // Preparar dados para salvar
        $data = [
            'usuario_id' => (int) $this->dataForm['usuario_id'],
            'evaluation_model_id' => (int) $this->dataForm['evaluation_model_id'],
            'evaluation_question_id' => (int) $this->dataForm['evaluation_question_id'],
            'resposta' => trim($this->dataForm['resposta']),
            'pontuacao' => !empty($this->dataForm['pontuacao']) ? (float) $this->dataForm['pontuacao'] : null,
            'comentario' => !empty($this->dataForm['comentario']) ? trim($this->dataForm['comentario']) : null,
            'status' => $this->dataForm['status'] ?? 'ativo',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Instanciar o repository
        $answersRepository = new EvaluationAnswersRepository();

        // Tentar criar a resposta
        if ($answersRepository->create($data)) {
            $urlRedirect = getenv('URL_ADM') . 'list-evaluation-answers/index';
            header("Location: $urlRedirect");
            exit;
        } else {
            $this->data['error'] = "Erro: Não foi possível cadastrar a resposta. Tente novamente!";
        }
    }
} 