<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationModelsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;

/**
 * Controller para atualizar modelo de avaliação
 * 
 * @package App\adms\Controllers\evaluations
 */
class UpdateEvaluationModel
{
    private array $data = [];

    public function index($id = null): void
    {
        // Converter para int se for string
        if (is_string($id)) {
            $id = !empty($id) ? (int)$id : null;
        }

        if (!$id) {
            $_SESSION['msg'] = 'ID do modelo não informado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->atualizarModelo($id);
            return;
        }

        // Buscar modelo
        $modelsRepo = new EvaluationModelsRepository();
        $model = $modelsRepo->getModel($id);

        if (!$model) {
            $_SESSION['msg'] = 'Modelo não encontrado!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;
        }

        $this->data['model'] = $model;
        $this->data['form'] = $model;

        // Carregar treinamentos
        $trainingsRepo = new TrainingsRepository();
        $this->data['trainings'] = $trainingsRepo->getAllTrainings();

        // Carregar questões existentes
        $questionsRepo = new EvaluationQuestionsRepository();
        $criteria = ['model_id' => $id];
        $questoes = $questionsRepo->getAllQuestions($criteria, 1, 1000);
        
        // Processar questões para o JavaScript
        foreach ($questoes as &$questao) {
            if ($questao['tipo'] === 'multipla_escolha' && !empty($questao['opcoes'])) {
                $questao['alternativas'] = explode("\n", $questao['opcoes']);
            }
        }
        $this->data['questoes'] = $questoes;

        $pageElements = [
            'title_head' => 'Editar Modelo de Avaliação',
            'menu' => 'list-evaluation-models',
            'buttonPermission' => ['UpdateEvaluationModel'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/evaluations/models/edit', $this->data);
        $loadView->loadView();
    }

    private function atualizarModelo(int $id): void
    {
        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_update_evaluation_model', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            // Buscar dados antigos
            $modelsRepo = new EvaluationModelsRepository();
            $dadosAntes = $modelsRepo->getModel($id);

            if (!$dadosAntes) {
                throw new \Exception('Modelo não encontrado!');
            }

            // Validações
            if (empty($_POST['titulo'])) {
                throw new \Exception('O título é obrigatório!');
            }

            if (empty($_POST['adms_training_id'])) {
                throw new \Exception('Selecione um treinamento!');
            }

            // Preparar dados
            $dadosDepois = [
                'training_id' => (int)$_POST['adms_training_id'],
                'titulo' => trim($_POST['titulo']),
                'codigo_documento' => trim($_POST['codigo_documento'] ?? ''),
                'versao_documento' => trim($_POST['versao_documento'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? ''),
                'nota_minima_aprovacao' => (float)($_POST['nota_minima_aprovacao'] ?? 7.00),
                'tempo_limite' => !empty($_POST['tempo_limite']) ? (int)$_POST['tempo_limite'] : null,
                'max_tentativas' => !empty($_POST['max_tentativas']) ? (int)$_POST['max_tentativas'] : null,
                'permitir_refazer' => isset($_POST['permitir_refazer']) ? 1 : 0,
                'mostrar_gabarito' => isset($_POST['mostrar_gabarito']) ? 1 : 0,
                'embaralhar_questoes' => isset($_POST['embaralhar_questoes']) ? 1 : 0,
                'ativo' => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1
            ];

            // Usar transação para garantir consistência
            $conn = $modelsRepo->getConnection();
            $conn->beginTransaction();

            // Atualizar modelo
            $resultado = $modelsRepo->updateModel($id, $dadosDepois);

            if (!$resultado) {
                throw new \Exception('Erro ao atualizar modelo!');
            }

            // Processar questões
            $questoes = $_POST['questoes'] ?? [];
            
            // LOG: Debug das questões recebidas
            GenerateLog::generateLog("info", "UPDATE: Questões recebidas", [
                'model_id' => $id,
                'total_questoes_recebidas' => count($questoes),
                'questoes' => $questoes,
                'POST_completo' => $_POST,
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
            
            // Debug adicional para ver o que está vindo
            error_log("=== UPDATE EVALUATION MODEL ===");
            error_log("Model ID: $id");
            error_log("Total questões recebidas: " . count($questoes));
            error_log("Questões: " . print_r($questoes, true));
            
            $questionsRepo = new EvaluationQuestionsRepository();
            
            // Buscar IDs das questões existentes
            $questoesExistentes = $questionsRepo->getAllQuestions(['model_id' => $id], 1, 1000);
            $idsExistentes = array_column($questoesExistentes, 'id');
            $idsRecebidos = [];
            
            $ordem = 1;
            foreach ($questoes as $idx => $questao) {
                error_log("Processando questão índice $idx: " . print_r($questao, true));
                
                // Validar questão
                if (empty($questao['pergunta']) || empty($questao['tipo'])) {
                    error_log("Questão $idx PULADA - pergunta ou tipo vazio");
                    continue;
                }
                
                error_log("Questão $idx VÁLIDA - processando...");
                
                $respostaCorreta = null;
                $opcoes = null;
                
                // Processar conforme tipo
                if ($questao['tipo'] === 'multipla_escolha') {
                    $alternativas = $questao['alternativas'] ?? [];
                    error_log("Alternativas recebidas: " . print_r($alternativas, true));
                    
                    if (!empty($alternativas)) {
                        // Filtrar alternativas vazias
                        $alternativas = array_filter($alternativas, function($alt) {
                            return !empty(trim($alt));
                        });
                        $alternativas = array_values($alternativas); // Reindexar
                        
                        $opcoes = implode("\n", $alternativas);
                        $indexCorreto = $questao['resposta_correta'] ?? null;
                        
                        error_log("Index da resposta correta: " . ($indexCorreto ?? 'NULL'));
                        error_log("Alternativas após filtrar: " . print_r($alternativas, true));
                        
                        if ($indexCorreto !== null && isset($alternativas[$indexCorreto])) {
                            $respostaCorreta = $alternativas[$indexCorreto];
                            error_log("Resposta correta definida: $respostaCorreta");
                        } else {
                            error_log("AVISO: Não foi possível definir resposta correta!");
                        }
                    }
                } elseif ($questao['tipo'] === 'verdadeiro_falso') {
                    $respostaCorreta = $questao['resposta_correta_vf'] ?? 'Verdadeiro';
                } elseif ($questao['tipo'] === 'texto' || $questao['tipo'] === 'numerica') {
                    $respostaCorreta = !empty($questao['resposta_correta_texto']) ? $questao['resposta_correta_texto'] : null;
                }
                
                $questionData = [
                    'model_id' => $id,
                    'pergunta' => trim($questao['pergunta']),
                    'tipo' => $questao['tipo'],
                    'opcoes' => $opcoes,
                    'resposta_correta' => $respostaCorreta,
                    'pontos' => (float)($questao['pontos'] ?? 1.00),
                    'explicacao' => !empty($questao['explicacao']) ? trim($questao['explicacao']) : null,
                    'ordem' => $ordem++
                ];
                
                // Se tem ID, é atualização; senão, é criação
                if (!empty($questao['id']) && is_numeric($questao['id'])) {
                    $questionId = (int)$questao['id'];
                    $idsRecebidos[] = $questionId;
                    $questionsRepo->updateQuestion($questionId, $questionData);
                    
                    EvaluationLogService::logQuestionUpdated(
                        $questionId,
                        [],
                        $questionData,
                        $_SESSION['user_id'] ?? 1
                    );
                } else {
                    $questionsRepo->createQuestion($questionData);
                    $questionId = $conn->lastInsertId();
                    $idsRecebidos[] = $questionId;
                    
                    EvaluationLogService::logQuestionCreated(
                        $questionId,
                        $questionData,
                        $_SESSION['user_id'] ?? 1
                    );
                }
            }
            
            // Remover questões que foram deletadas (existem no banco mas não foram enviadas)
            $idsParaRemover = array_diff($idsExistentes, $idsRecebidos);
            foreach ($idsParaRemover as $idRemover) {
                $questionsRepo->deleteQuestion($idRemover);
                
                EvaluationLogService::logQuestionDeleted(
                    $idRemover,
                    ['model_id' => $id],
                    $_SESSION['user_id'] ?? 1
                );
            }
            
            $conn->commit();

            // LOG
            EvaluationLogService::logModelUpdated(
                $id,
                $dadosAntes,
                $dadosDepois,
                $_SESSION['user_id'] ?? 1
            );

            $_SESSION['msg'] = 'Modelo e questões atualizados com sucesso!';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-models');
            exit;

        } catch (\Exception $e) {
            // Rollback em caso de erro
            $modelsRepo = new EvaluationModelsRepository();
            $conn = $modelsRepo->getConnection();
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            EvaluationLogService::logError('atualizar_modelo', $e, ['model_id' => $id]);
            
            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-evaluation-model/' . $id);
            exit;
        }
    }
}
