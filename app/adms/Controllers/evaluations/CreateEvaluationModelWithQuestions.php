<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;

/**
 * Controller para criar modelo de avaliação com questões em uma única tela (Form Builder)
 * 
 * @package App\adms\Controllers\evaluations
 */
class CreateEvaluationModelWithQuestions
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarQuestionarioCompleto();
            return;
        }

        // Carregar treinamentos para o select
        $trainingsRepo = new TrainingsRepository();
        $this->data['trainings'] = $trainingsRepo->getAllTrainings();

        // Valores padrão do formulário
        $this->data['form'] = [
            'adms_training_id' => '',
            'titulo' => '',
            'descricao' => '',
            'nota_minima_aprovacao' => 7.00,
            'tempo_limite' => '',
            'max_tentativas' => '',
            'permitir_refazer' => 1,
            'mostrar_gabarito' => 1,
            'embaralhar_questoes' => 0,
            'ativo' => 1
        ];

        $pageElements = [
            'title_head' => 'Criar Questionário Completo',
            'menu' => 'create-evaluation-model',
            'buttonPermission' => ['CreateEvaluationModel'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/evaluations/models/createWithQuestions', $this->data);
        $loadView->loadView();
    }

    private function salvarQuestionarioCompleto(): void
    {
        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_create_evaluation_full', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            // Usar repositório para obter conexão
            $modelsRepo = new EvaluationModelsRepository();
            $conn = $modelsRepo->getConnection();
            $conn->beginTransaction();

            // LOG: Início da operação
            EvaluationLogService::logError('criar_questionario_completo', new \Exception('Iniciando criação'), [
                'titulo' => $_POST['titulo'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null,
                'total_questoes' => count($_POST['questoes'] ?? [])
            ]);

            // Validações básicas
            if (empty($_POST['titulo'])) {
                throw new \Exception('O título é obrigatório!');
            }

            if (empty($_POST['adms_training_id'])) {
                throw new \Exception('Selecione um treinamento!');
            }

            $questoes = $_POST['questoes'] ?? [];
            if (empty($questoes)) {
                throw new \Exception('Adicione pelo menos uma questão ao questionário!');
            }

            // 1. Salvar modelo
            $modelData = [
                'training_id' => (int)$_POST['adms_training_id'],
                'titulo' => trim($_POST['titulo']),
                'descricao' => trim($_POST['descricao'] ?? ''),
                'nota_minima_aprovacao' => (float)($_POST['nota_minima_aprovacao'] ?? 7.00),
                'tempo_limite' => !empty($_POST['tempo_limite']) ? (int)$_POST['tempo_limite'] : null,
                'max_tentativas' => !empty($_POST['max_tentativas']) ? (int)$_POST['max_tentativas'] : null,
                'permitir_refazer' => isset($_POST['permitir_refazer']) ? 1 : 0,
                'mostrar_gabarito' => isset($_POST['mostrar_gabarito']) ? 1 : 0,
                'embaralhar_questoes' => isset($_POST['embaralhar_questoes']) ? 1 : 0,
                'ativo' => 1
            ];

            $modelsRepo = new EvaluationModelsRepository();
            $modelsRepo->createModel($modelData);
            $modelId = $conn->lastInsertId();

            if (!$modelId) {
                throw new \Exception('Erro ao criar modelo de avaliação!');
            }

            // LOG: Modelo criado
            EvaluationLogService::logModelCreated(
                $modelId,
                array_merge($modelData, ['questoes' => $questoes]),
                $_SESSION['user_id'] ?? 1
            );

            // 2. Salvar questões
            $questionsRepo = new EvaluationQuestionsRepository();
            $ordem = 1;
            $questoesCriadas = 0;

            foreach ($questoes as $questao) {
                // Validar questão
                if (empty($questao['pergunta']) || empty($questao['tipo'])) {
                    continue; // Pula questões inválidas
                }

                $respostaCorreta = null;
                $opcoes = null;

                // Processar conforme tipo
                if ($questao['tipo'] === 'multipla_escolha') {
                    // Juntar alternativas
                    $alternativas = $questao['alternativas'] ?? [];
                    if (empty($alternativas)) {
                        continue; // Pula se não tem alternativas
                    }
                    
                    $opcoes = implode("\n", $alternativas);
                    
                    // Resposta correta é o índice marcado
                    $indexCorreto = $questao['resposta_correta'] ?? null;
                    if ($indexCorreto !== null && isset($alternativas[$indexCorreto])) {
                        $respostaCorreta = $alternativas[$indexCorreto];
                    }
                    
                } elseif ($questao['tipo'] === 'verdadeiro_falso') {
                    $respostaCorreta = $questao['resposta_correta_vf'] ?? 'Verdadeiro';
                    
                } elseif ($questao['tipo'] === 'texto' || $questao['tipo'] === 'numerica') {
                    // Para texto e numérica, o gabarito é opcional
                    $respostaCorreta = !empty($questao['resposta_correta_texto']) ? $questao['resposta_correta_texto'] : null;
                }

                $questionData = [
                    'model_id' => $modelId,
                    'pergunta' => trim($questao['pergunta']),
                    'tipo' => $questao['tipo'],
                    'opcoes' => $opcoes,
                    'resposta_correta' => $respostaCorreta,
                    'pontos' => (float)($questao['pontos'] ?? 1.00),
                    'explicacao' => !empty($questao['explicacao']) ? trim($questao['explicacao']) : null,
                    'ordem' => $ordem++
                ];

                $questionsRepo->createQuestion($questionData);
                $questionId = $conn->lastInsertId();

                if ($questionId) {
                    // LOG: Questão criada
                    EvaluationLogService::logQuestionCreated(
                        $questionId,
                        $questionData,
                        $_SESSION['user_id'] ?? 1
                    );
                    $questoesCriadas++;
                }
            }

            if ($questoesCriadas === 0) {
                throw new \Exception('Nenhuma questão válida foi adicionada!');
            }

            $conn->commit();

            // LOG: Sucesso total
            \App\adms\Helpers\GenerateLog::generateLog("info", "Questionário completo criado com SUCESSO", [
                'model_id' => $modelId,
                'titulo' => $_POST['titulo'],
                'total_questoes_criadas' => $questoesCriadas,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);

            $_SESSION['msg'] = "Questionário criado com sucesso! {$questoesCriadas} questão(ões) adicionada(s).";
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;

        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }

            // LOG: Erro crítico
            EvaluationLogService::logError('criar_questionario_completo', $e, [
                'titulo' => $_POST['titulo'] ?? '',
                'total_questoes' => count($_POST['questoes'] ?? [])
            ]);
            
            $_SESSION['msg'] = 'Erro ao criar questionário: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-evaluation-model-with-questions');
            exit;
        }
    }
}

