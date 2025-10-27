<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\EvaluationLogService;

/**
 * Controller responsável em atualizar perguntas de avaliação.
 *
 * Esta classe gerencia a atualização de perguntas de avaliação, incluindo validação de dados,
 * verificação de permissões e carregamento de views. Ela utiliza o `EvaluationQuestionsRepository` 
 * para atualizar dados no banco e o `LoadViewService` para carregar as views.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class UpdateEvaluationQuestion
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = [];

    /** @var array $dataForm Recebe os dados do formulário */
    private array $dataForm = [];

    /**
     * Método principal que executa a atualização de perguntas de avaliação.
     *
     * @param int|string|null $id ID da pergunta a ser atualizada
     * @return void
     */
    public function index($id = null): void
    {
        // Converter para int se for string
        if (is_string($id)) {
            $id = !empty($id) ? (int)$id : null;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID da pergunta não informado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;
        }

        // Buscar pergunta
        $questionsRepository = new EvaluationQuestionsRepository();
        $question = $questionsRepository->getQuestion($id);

        if (!$question) {
            $_SESSION['msg'] = 'Pergunta não encontrada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;
        }

        // Se for POST, processar atualização
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateQuestion($id, $question);
            return;
        }

        // Preparar dados para exibição
        $this->data['question'] = $question;
        $this->data['form'] = $question;

        // Buscar modelos para o select
        $modelsRepository = new EvaluationModelsRepository();
        $this->data['models'] = $modelsRepository->getAllModels();

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Editar Pergunta de Avaliação',
            'menu' => 'list-evaluation-questions',
            'buttonPermission' => ['ListEvaluationQuestions', 'DeleteEvaluationQuestion', 'ViewEvaluationQuestion'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Gerar novo token CSRF
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_update_evaluation_question');

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/evaluations/questions/edit", $this->data);
        $loadView->loadView();
    }

    /**
     * Atualizar a pergunta no banco de dados.
     *
     * @param int $id ID da pergunta
     * @param array $dadosAntes Dados anteriores da pergunta
     * @return void
     */
    private function updateQuestion(int $id, array $dadosAntes): void
    {
        try {
            // Receber os dados do formulário
            $this->dataForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

            // Validar CSRF token
            if (!CSRFHelper::validateCSRFToken('form_update_evaluation_question', $this->dataForm['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            // Validar dados
            $this->validateData();

            // Se houver erro, redirecionar de volta
            if (!empty($this->data['error'])) {
                throw new \Exception($this->data['error']);
            }

            // Preparar dados para atualizar
            $dadosDepois = [
                'model_id' => (int) $this->dataForm['model_id'],
                'pergunta' => trim($this->dataForm['pergunta']),
                'tipo' => $this->dataForm['tipo'],
                'opcoes' => !empty($this->dataForm['opcoes']) ? trim($this->dataForm['opcoes']) : null,
                'resposta_correta' => !empty($this->dataForm['resposta_correta']) ? trim($this->dataForm['resposta_correta']) : null,
                'pontos' => !empty($this->dataForm['pontos']) ? (float) $this->dataForm['pontos'] : 1.00,
                'explicacao' => !empty($this->dataForm['explicacao']) ? trim($this->dataForm['explicacao']) : null,
                'ordem' => !empty($this->dataForm['ordem']) ? (int) $this->dataForm['ordem'] : 1
            ];

            // Instanciar o repository
            $questionsRepository = new EvaluationQuestionsRepository();

            // Tentar atualizar a pergunta
            if (!$questionsRepository->updateQuestion($id, $dadosDepois)) {
                throw new \Exception('Erro ao atualizar a pergunta. Tente novamente!');
            }

            // LOG
            EvaluationLogService::logQuestionUpdated(
                $id,
                $dadosAntes,
                $dadosDepois,
                $_SESSION['user_id'] ?? 1
            );

            $_SESSION['msg'] = 'Pergunta atualizada com sucesso!';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-questions');
            exit;

        } catch (\Exception $e) {
            EvaluationLogService::logError('atualizar_pergunta', $e, ['question_id' => $id]);
            
            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-evaluation-question/' . $id);
            exit;
        }
    }

    /**
     * Validar os dados do formulário.
     *
     * @return void
     */
    private function validateData(): void
    {
        // Validar modelo
        if (empty($this->dataForm['model_id'])) {
            $this->data['error'] = "Selecione um modelo de avaliação!";
            return;
        }

        // Validar pergunta
        if (empty($this->dataForm['pergunta'])) {
            $this->data['error'] = "Digite a pergunta!";
            return;
        }

        if (strlen($this->dataForm['pergunta']) < 10) {
            $this->data['error'] = "A pergunta deve ter pelo menos 10 caracteres!";
            return;
        }

        // Validar tipo
        $tiposValidos = ['texto', 'multipla_escolha', 'verdadeiro_falso', 'numerica'];
        if (empty($this->dataForm['tipo']) || !in_array($this->dataForm['tipo'], $tiposValidos)) {
            $this->data['error'] = "Selecione um tipo válido!";
            return;
        }

        // Validar opções para múltipla escolha
        if ($this->dataForm['tipo'] === 'multipla_escolha' && empty($this->dataForm['opcoes'])) {
            $this->data['error'] = "Para perguntas de múltipla escolha, informe as opções!";
            return;
        }

        // Validar ordem
        if (!empty($this->dataForm['ordem']) && !is_numeric($this->dataForm['ordem'])) {
            $this->data['error'] = "A ordem deve ser um número!";
            return;
        }

        // Validar pontos
        if (!empty($this->dataForm['pontos']) && !is_numeric($this->dataForm['pontos'])) {
            $this->data['error'] = "Os pontos devem ser um número!";
            return;
        }

        // Se chegou até aqui, não há erros
        $this->data['error'] = null;
    }
}

