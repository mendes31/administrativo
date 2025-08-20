<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller responsável em criar perguntas de avaliação.
 *
 * Esta classe gerencia a criação de perguntas de avaliação, incluindo validação de dados,
 * verificação de permissões e carregamento de views. Ela utiliza o `EvaluationQuestionsRepository` 
 * para salvar dados no banco e o `LoadViewService` para carregar as views.
 *
 * @package App\adms\Controllers\evaluations
 * @author Rafael Mendes
 */
class CreateEvaluationQuestion
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array $dataForm Recebe os dados do formulário */
    private array $dataForm = [];

    /**
     * Método principal que executa a criação de perguntas de avaliação.
     *
     * Este método verifica permissões, processa formulários, valida dados e salva
     * no banco de dados.
     *
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->dataForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o formulário foi enviado
        if (!empty($this->dataForm['SendAddQuestion'])) {
            // Remover o botão do array
            unset($this->dataForm['SendAddQuestion']);

            // Validar CSRF token
            if (!CSRFHelper::validateCSRFToken('form_create_evaluation_question', $this->dataForm['csrf_token'])) {
                $this->data['error'] = "Token de segurança inválido. Tente novamente.";
            } else {
                // Validar dados
                $this->validateData();

                // Se não houver erro, salvar no banco
                if (empty($this->data['error'])) {
                    $this->createQuestion();
                }
            }
        }

        // Buscar modelos para o select
        $modelsRepository = new EvaluationModelsRepository();
        $this->data['models'] = $modelsRepository->getAllModels();

        // Definir o título da página, ativar o item de menu e apresentar ou ocultar botões
        $pageElements = [
            'title_head' => 'Cadastrar Pergunta de Avaliação',
            'menu' => 'create-evaluation-question',
            'buttonPermission' => ['ListEvaluationQuestions'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Gerar novo token CSRF
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_create_evaluation_question');

        // Adicionar URL base ao array de dados (se necessário)
        // $this->data['url'] = getenv('URL_ADM'); // Só se realmente precisar passar para a view

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/evaluations/questions/create", $this->data);
        $loadView->loadView();
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
            $this->data['error'] = "Erro: Selecione um modelo de avaliação!";
            return;
        }

        // Validar pergunta
        if (empty($this->dataForm['pergunta'])) {
            $this->data['error'] = "Erro: Digite a pergunta!";
            return;
        }

        if (strlen($this->dataForm['pergunta']) < 10) {
            $this->data['error'] = "Erro: A pergunta deve ter pelo menos 10 caracteres!";
            return;
        }

        // Validar tipo
        $tiposValidos = ['texto', 'multipla_escolha', 'verdadeiro_falso', 'numerica'];
        if (empty($this->dataForm['tipo']) || !in_array($this->dataForm['tipo'], $tiposValidos)) {
            $this->data['error'] = "Erro: Selecione um tipo válido!";
            return;
        }

        // Validar opções para múltipla escolha
        if ($this->dataForm['tipo'] === 'multipla_escolha' && empty($this->dataForm['opcoes'])) {
            $this->data['error'] = "Erro: Para perguntas de múltipla escolha, informe as opções!";
            return;
        }

        // Validar ordem
        if (!empty($this->dataForm['ordem']) && !is_numeric($this->dataForm['ordem'])) {
            $this->data['error'] = "Erro: A ordem deve ser um número!";
            return;
        }

        // Se chegou até aqui, não há erros
        $this->data['error'] = null;
    }

    /**
     * Criar a pergunta no banco de dados.
     *
     * @return void
     */
    private function createQuestion(): void
    {
        // Preparar dados para salvar
        $data = [
            'model_id' => (int) $this->dataForm['model_id'],
            'pergunta' => trim($this->dataForm['pergunta']),
            'tipo' => $this->dataForm['tipo'],
            'opcoes' => !empty($this->dataForm['opcoes']) ? trim($this->dataForm['opcoes']) : null,
            'ordem' => !empty($this->dataForm['ordem']) ? (int) $this->dataForm['ordem'] : 1
        ];

        // Instanciar o repository
        $questionsRepository = new EvaluationQuestionsRepository();

        // Tentar criar a pergunta
        if ($questionsRepository->createQuestion($data)) {
            $this->data['success'] = "Pergunta cadastrada com sucesso!";
            $this->dataForm = []; // Limpar formulário
        } else {
            $this->data['error'] = "Erro: Não foi possível cadastrar a pergunta. Tente novamente!";
        }
    }
} 