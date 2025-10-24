<?php

namespace App\adms\Controllers\evaluations;

use App\adms\Models\Repository\EvaluationAssignmentsRepository;
use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;

/**
 * Controller para cancelar atribuições de avaliações
 * 
 * @package App\adms\Controllers\evaluations
 */
class CancelEvaluationAssignment
{
    private $conn;

    public function __construct()
    {
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $this->conn = $assignmentsRepo->getConnection();
    }

    /**
     * Cancelar atribuição individual (via POST)
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-assignments');
            exit;
        }

        try {
            // Validar CSRF
            if (!CSRFHelper::validateCSRFToken('form_cancel_assignment', $_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de segurança inválido!');
            }

            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $motivo = trim($_POST['motivo_cancelamento'] ?? 'Cancelado manualmente');

            if (!$assignmentId) {
                throw new \Exception('ID da atribuição não informado!');
            }

            $resultado = $this->cancelarAtribuicao(
                $assignmentId,
                $_SESSION['user_id'] ?? 1,
                $motivo,
                'manual'
            );

            $_SESSION['msg'] = $resultado['mensagem'];
            $_SESSION['msg_type'] = $resultado['sucesso'] ? 'success' : 'warning';

        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao cancelar atribuição", [
                'assignment_id' => $assignmentId ?? 0,
                'error' => $e->getMessage()
            ]);

            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-evaluation-assignments');
        exit;
    }

    /**
     * Método público para cancelar atribuição
     */
    public function cancelarAtribuicao(
        int $assignmentId,
        int $canceladoPor,
        string $motivo = '',
        string $tipo = 'manual'
    ): array {
        try {
            $this->conn->beginTransaction();

            $assignmentsRepo = new EvaluationAssignmentsRepository();

            // Buscar atribuição
            $assignment = $assignmentsRepo->getById($assignmentId);

            if (!$assignment) {
                throw new \Exception('Atribuição não encontrada!');
            }

            // Verificar se pode cancelar
            $statusNaoCancelaveis = ['aprovado', 'concluido', 'cancelado'];

            if (in_array($assignment['status'], $statusNaoCancelaveis)) {
                return [
                    'sucesso' => false,
                    'mensagem' => "Não é possível cancelar uma avaliação com status: {$assignment['status']}"
                ];
            }

            // Cancelar
            $resultado = $assignmentsRepo->cancelAssignment($assignmentId, $canceladoPor, $motivo);

            if (!$resultado) {
                throw new \Exception('Erro ao cancelar atribuição!');
            }

            // LOG
            EvaluationLogService::logAssignmentCancelled(
                $assignmentId,
                $assignment,
                $canceladoPor,
                $motivo,
                $tipo
            );

            $this->conn->commit();

            $mensagem = sprintf(
                'Avaliação "%s" cancelada para %s. Histórico de %d tentativa(s) preservado.',
                $assignment['model_titulo'] ?? 'N/A',
                $assignment['user_name'] ?? 'usuário',
                $assignment['tentativas'] ?? 0
            );

            return [
                'sucesso' => true,
                'mensagem' => $mensagem
            ];

        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Cancelar todas as atribuições de um usuário (quando inativa)
     */
    public function cancelarAvaliacoesUsuario(
        int $userId,
        int $canceladoPor,
        string $motivo = 'Usuário inativado'
    ): array {
        $assignmentsRepo = new EvaluationAssignmentsRepository();
        $assignments = $assignmentsRepo->getActiveAssignmentsByUser($userId);

        $canceladas = 0;
        $erros = [];

        foreach ($assignments as $assignment) {
            try {
                // Cancelar diretamente no repositório (sem transações aninhadas)
                $resultado = $assignmentsRepo->cancelAssignment(
                    $assignment['id'],
                    $canceladoPor,
                    $motivo
                );

                if ($resultado) {
                    $canceladas++;
                    
                    // LOG individual
                    EvaluationLogService::logAssignmentCancelled(
                        $assignment['id'],
                        $assignment,
                        $canceladoPor,
                        $motivo,
                        'automatico_inativacao'
                    );
                }

            } catch (\Exception $e) {
                $erros[] = "Erro ao cancelar '{$assignment['titulo']}': " . $e->getMessage();
                
                GenerateLog::generateLog("error", "Erro ao cancelar avaliação individual", [
                    'assignment_id' => $assignment['id'],
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        GenerateLog::generateLog("info", "Avaliações canceladas automaticamente (inativação)", [
            'user_id' => $userId,
            'total_canceladas' => $canceladas,
            'total_encontradas' => count($assignments),
            'motivo' => $motivo
        ]);

        return [
            'sucesso' => true,
            'total_canceladas' => $canceladas,
            'total_encontradas' => count($assignments),
            'erros' => $erros
        ];
    }
}

