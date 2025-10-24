<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Repository\EvaluationQuestionsRepository;
use App\adms\Models\Repository\EvaluationAttemptsRepository;
use App\adms\Models\Repository\EvaluationAnswersRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Services\EvaluationGradingService;
use App\adms\Helpers\EvaluationLogService;

/**
 * Controller para responder avaliação (todas as questões em uma tela)
 * 
 * @package App\adms\Controllers\evaluations
 */
class AnswerEvaluation
{
    private array $data = [];
    private $conn;

    public function __construct()
    {
        // Usar repositório para obter conexão
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $this->conn = $assignmentsRepo->getConnection();
    }

    public function index($assignmentId = null): void
    {
        // Converter para int se for string
        if (is_string($assignmentId)) {
            $assignmentId = !empty($assignmentId) ? (int)$assignmentId : null;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $_SESSION['msg'] = 'Faça login para acessar!';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if (!$assignmentId) {
            $_SESSION['msg'] = 'Atribuição não informada!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // POST = Enviar respostas
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processarRespostas($assignmentId, $userId);
            return;
        }

        // GET = Exibir questionário
        $this->exibirQuestionario($assignmentId, $userId);
    }

    private function exibirQuestionario(int $assignmentId, int $userId): void
    {
        $assignmentsRepo = new EvaluationAssignmentsRepository();

        // Buscar atribuição
        $assignment = $assignmentsRepo->getById($assignmentId);

        if (!$assignment || $assignment['adms_user_id'] != $userId) {
            $_SESSION['msg'] = 'Avaliação não encontrada ou você não tem permissão!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Verificar se pode responder
        if (!$assignmentsRepo->canUserAnswer($assignmentId)) {
            $_SESSION['msg'] = 'Você não pode mais responder esta avaliação!';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Buscar questões
        $questionsRepo = new EvaluationQuestionsRepository();
        $criteria = ['model_id' => $assignment['evaluation_model_id']];
        $questoes = $questionsRepo->getAllQuestions($criteria, 1, 1000);

        if (empty($questoes)) {
            $_SESSION['msg'] = 'Este questionário não possui questões!';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }

        // Processar opções de múltipla escolha
        foreach ($questoes as &$questao) {
            if ($questao['tipo'] === 'multipla_escolha' && !empty($questao['opcoes'])) {
                $questao['alternativas'] = explode("\n", trim($questao['opcoes']));
            }
        }

        $this->data['assignment'] = $assignment;
        $this->data['questoes'] = $questoes;
        $this->data['total_pontos'] = array_sum(array_column($questoes, 'pontos'));

        // Marcar como "em_andamento"
        if ($assignment['status'] === 'pendente') {
            $assignmentsRepo->update($assignmentId, [
                'status' => 'em_andamento',
                'tentativas' => $assignment['tentativas'],
                'nota_maxima' => $assignment['nota_maxima'],
                'data_limite' => $assignment['data_limite']
            ]);

            // LOG
            EvaluationLogService::logAttemptStarted(
                $assignmentId,
                $userId,
                $assignment['tentativas'] + 1
            );
        }

        // Layout
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements([
            'title_head' => 'Responder Avaliação',
            'menu' => 'my-evaluations',
            'buttonPermission' => [],
        ]));

        $loadView = new LoadViewService('adms/Views/evaluations/answer/answerEvaluation', $this->data);
        $loadView->loadView();
    }

    private function processarRespostas(int $assignmentId, int $userId): void
    {
        try {
            $this->conn->beginTransaction();

            $assignmentsRepo = new EvaluationAssignmentsRepository();
            $questionsRepo = new EvaluationQuestionsRepository();
            $attemptsRepo = new EvaluationAttemptsRepository();
            $answersRepo = new EvaluationAnswersRepository();

            // Buscar atribuição
            $assignment = $assignmentsRepo->getById($assignmentId);

            if (!$assignment || $assignment['adms_user_id'] != $userId) {
                throw new \Exception('Avaliação não encontrada!');
            }

            if (!$assignmentsRepo->canUserAnswer($assignmentId)) {
                throw new \Exception('Você não pode mais responder esta avaliação!');
            }

            // Buscar questões
            $criteria = ['model_id' => $assignment['evaluation_model_id']];
            $questoes = $questionsRepo->getAllQuestions($criteria, 1, 1000);

            // Respostas do usuário
            $respostasUsuario = $_POST['respostas'] ?? [];

            // Calcular nota usando o serviço
            $resultado = EvaluationGradingService::calcularNota($questoes, $respostasUsuario);

            // Salvar cada resposta individual
            foreach ($resultado['resultados_detalhados'] as $detalhe) {
                $answersRepo->create([
                    'usuario_id' => $userId,
                    'evaluation_model_id' => $assignment['evaluation_model_id'],
                    'evaluation_question_id' => $detalhe['questao_id'],
                    'resposta' => $detalhe['resposta'],
                    'pontuacao' => $detalhe['pontos_obtidos'],
                    'comentario' => null,
                    'status' => 'respondido',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Determinar aprovação
            $aprovado = EvaluationGradingService::verificarAprovacao(
                $resultado['nota_final'],
                $assignment['nota_minima_aprovacao']
            );

            $novoStatus = $aprovado ? 'aprovado' : 'reprovado';
            $novaTentativa = $assignment['tentativas'] + 1;

            // Registrar tentativa
            $attemptId = $attemptsRepo->insert([
                'assignment_id' => $assignmentId,
                'tentativa_numero' => $novaTentativa,
                'nota_obtida' => $resultado['nota_final'],
                'total_questoes' => $resultado['total_questoes'],
                'questoes_corretas' => $resultado['questoes_corretas'],
                'questoes_erradas' => $resultado['questoes_erradas'],
                'percentual' => $resultado['percentual'],
                'respostas' => json_encode($resultado['resultados_detalhados'], JSON_UNESCAPED_UNICODE),
                'data_inicio' => date('Y-m-d H:i:s'),
                'data_finalizacao' => date('Y-m-d H:i:s'),
                'tempo_gasto' => null
            ]);

            // Atualizar assignment
            $novaMelhorNota = max($assignment['nota_maxima'] ?? 0, $resultado['nota_final']);

            $assignmentsRepo->update($assignmentId, [
                'status' => $novoStatus,
                'tentativas' => $novaTentativa,
                'nota_maxima' => $novaMelhorNota,
                'data_limite' => $assignment['data_limite']
            ]);

            // LOG
            EvaluationLogService::logAttemptCompleted($attemptId, [
                'assignment_id' => $assignmentId,
                'tentativa_numero' => $novaTentativa,
                'nota_obtida' => $resultado['nota_final'],
                'nota_minima' => $assignment['nota_minima_aprovacao'],
                'percentual' => $resultado['percentual'],
                'questoes_corretas' => $resultado['questoes_corretas'],
                'questoes_erradas' => $resultado['questoes_erradas']
            ], $userId);

            $this->conn->commit();

            $_SESSION['msg'] = 'Avaliação enviada com sucesso!';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'resultado-avaliacao/' . $attemptId);
            exit;

        } catch (\Exception $e) {
            $this->conn->rollBack();

            EvaluationLogService::logError('processar_respostas', $e, [
                'assignment_id' => $assignmentId,
                'user_id' => $userId
            ]);

            $_SESSION['msg'] = 'Erro ao processar avaliação: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'minhas-avaliacoes');
            exit;
        }
    }
}

