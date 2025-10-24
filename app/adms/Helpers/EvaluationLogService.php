<?php

namespace App\adms\Helpers;

use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Controllers\Services\RequestHelper;

/**
 * Serviço centralizado para logs do módulo de avaliações
 * Utiliza GenerateLog (Monolog) para logs em arquivo
 * Utiliza LogAlteracaoService para auditoria no banco
 * 
 * @package App\adms\Helpers
 */
class EvaluationLogService
{
    /**
     * Log de criação de modelo de avaliação
     */
    public static function logModelCreated(int $modelId, array $data, int $userId): void
    {
        GenerateLog::generateLog("info", "Modelo de avaliação criado", [
            'model_id' => $modelId,
            'titulo' => $data['titulo'] ?? '',
            'training_id' => $data['training_id'] ?? null,
            'total_questoes' => count($data['questoes'] ?? []),
            'nota_minima' => $data['nota_minima_aprovacao'] ?? 7.00,
            'user_id' => $userId,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_models',
            $modelId,
            $userId,
            'INSERT',
            [],
            [
                'titulo' => $data['titulo'] ?? '',
                'training_id' => $data['training_id'] ?? null,
                'nota_minima_aprovacao' => $data['nota_minima_aprovacao'] ?? 7.00,
                'permitir_refazer' => $data['permitir_refazer'] ?? 1,
                'max_tentativas' => $data['max_tentativas'] ?? null,
                'ativo' => 1
            ]
        );
    }

    /**
     * Log de atualização de modelo
     */
    public static function logModelUpdated(int $modelId, array $dadosAntes, array $dadosDepois, int $userId): void
    {
        GenerateLog::generateLog("info", "Modelo de avaliação atualizado", [
            'model_id' => $modelId,
            'titulo' => $dadosDepois['titulo'] ?? '',
            'alterado_por' => $userId,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_models',
            $modelId,
            $userId,
            'UPDATE',
            $dadosAntes,
            $dadosDepois
        );
    }

    /**
     * Log de deleção de modelo
     */
    public static function logModelDeleted(int $modelId, array $dadosAntes, int $userId): void
    {
        GenerateLog::generateLog("warning", "Modelo de avaliação DELETADO", [
            'model_id' => $modelId,
            'titulo' => $dadosAntes['titulo'] ?? '',
            'deletado_por' => $userId,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_models',
            $modelId,
            $userId,
            'DELETE',
            $dadosAntes,
            []
        );
    }

    /**
     * Log de criação de questão
     */
    public static function logQuestionCreated(int $questionId, array $data, int $userId): void
    {
        GenerateLog::generateLog("info", "Questão de avaliação criada", [
            'question_id' => $questionId,
            'model_id' => $data['model_id'] ?? null,
            'tipo' => $data['tipo'] ?? '',
            'pontos' => $data['pontos'] ?? 1.00,
            'tem_gabarito' => !empty($data['resposta_correta']),
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_questions',
            $questionId,
            $userId,
            'INSERT',
            [],
            [
                'model_id' => $data['model_id'] ?? null,
                'pergunta' => substr($data['pergunta'] ?? '', 0, 100) . '...',
                'tipo' => $data['tipo'] ?? '',
                'pontos' => $data['pontos'] ?? 1.00,
                'tem_resposta_correta' => !empty($data['resposta_correta'])
            ]
        );
    }

    /**
     * Log de atualização de questão
     */
    public static function logQuestionUpdated(int $questionId, array $dadosAntes, array $dadosDepois, int $userId): void
    {
        GenerateLog::generateLog("info", "Questão de avaliação atualizada", [
            'question_id' => $questionId,
            'model_id' => $dadosDepois['model_id'] ?? null,
            'tipo' => $dadosDepois['tipo'] ?? '',
            'pontos' => $dadosDepois['pontos'] ?? 1.00,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_questions',
            $questionId,
            $userId,
            'UPDATE',
            [
                'pergunta' => substr($dadosAntes['pergunta'] ?? '', 0, 100),
                'tipo' => $dadosAntes['tipo'] ?? '',
                'pontos' => $dadosAntes['pontos'] ?? 1.00
            ],
            [
                'pergunta' => substr($dadosDepois['pergunta'] ?? '', 0, 100),
                'tipo' => $dadosDepois['tipo'] ?? '',
                'pontos' => $dadosDepois['pontos'] ?? 1.00
            ]
        );
    }

    /**
     * Log de exclusão de questão
     */
    public static function logQuestionDeleted(int $questionId, array $dadosAntes, int $userId): void
    {
        GenerateLog::generateLog("warning", "Questão de avaliação deletada", [
            'question_id' => $questionId,
            'model_id' => $dadosAntes['model_id'] ?? null,
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_questions',
            $questionId,
            $userId,
            'DELETE',
            [
                'pergunta' => substr($dadosAntes['pergunta'] ?? '', 0, 100),
                'tipo' => $dadosAntes['tipo'] ?? '',
                'pontos' => $dadosAntes['pontos'] ?? 1.00
            ],
            []
        );
    }

    /**
     * Log de atribuição de avaliação
     */
    public static function logAssignmentCreated(int $assignmentId, array $data, int $criadoPor): void
    {
        GenerateLog::generateLog("info", "Avaliação atribuída a usuário", [
            'assignment_id' => $assignmentId,
            'model_id' => $data['evaluation_model_id'] ?? null,
            'user_id' => $data['adms_user_id'] ?? null,
            'data_limite' => $data['data_limite'] ?? null,
            'criado_por' => $criadoPor,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_assignments',
            $assignmentId,
            $criadoPor,
            'INSERT',
            [],
            [
                'evaluation_model_id' => $data['evaluation_model_id'] ?? null,
                'adms_user_id' => $data['adms_user_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
                'status' => 'pendente'
            ]
        );
    }

    /**
     * Log de início de tentativa
     */
    public static function logAttemptStarted(int $assignmentId, int $userId, int $tentativaNumero): void
    {
        GenerateLog::generateLog("info", "Usuário iniciou tentativa de avaliação", [
            'assignment_id' => $assignmentId,
            'user_id' => $userId,
            'tentativa_numero' => $tentativaNumero,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log de conclusão de tentativa
     */
    public static function logAttemptCompleted(int $attemptId, array $dadosAttempt, int $userId): void
    {
        $aprovado = $dadosAttempt['nota_obtida'] >= ($dadosAttempt['nota_minima'] ?? 7.00);

        GenerateLog::generateLog(
            $aprovado ? "info" : "warning",
            "Avaliação finalizada - " . ($aprovado ? "APROVADO" : "REPROVADO"),
            [
                'attempt_id' => $attemptId,
                'assignment_id' => $dadosAttempt['assignment_id'] ?? null,
                'user_id' => $userId,
                'tentativa_numero' => $dadosAttempt['tentativa_numero'] ?? 1,
                'nota_obtida' => $dadosAttempt['nota_obtida'] ?? 0,
                'nota_minima' => $dadosAttempt['nota_minima'] ?? 7.00,
                'percentual' => $dadosAttempt['percentual'] ?? 0,
                'questoes_corretas' => $dadosAttempt['questoes_corretas'] ?? 0,
                'questoes_erradas' => $dadosAttempt['questoes_erradas'] ?? 0,
                'aprovado' => $aprovado,
                'ip' => RequestHelper::getClientIp(),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_attempts',
            $attemptId,
            $userId,
            'INSERT',
            [],
            [
                'nota_obtida' => $dadosAttempt['nota_obtida'] ?? 0,
                'percentual' => $dadosAttempt['percentual'] ?? 0,
                'resultado' => $aprovado ? 'aprovado' : 'reprovado'
            ]
        );
    }

    /**
     * Log de cancelamento de atribuição
     */
    public static function logAssignmentCancelled(
        int $assignmentId, 
        array $dadosAntes, 
        int $canceladoPor, 
        string $motivo,
        string $tipo = 'manual'
    ): void {
        GenerateLog::generateLog(
            $tipo === 'automatico_inativacao' ? "warning" : "notice",
            "Avaliação CANCELADA - Tipo: " . $tipo,
            [
                'assignment_id' => $assignmentId,
                'user_id' => $dadosAntes['adms_user_id'] ?? null,
                'model_id' => $dadosAntes['evaluation_model_id'] ?? null,
                'status_anterior' => $dadosAntes['status'] ?? '',
                'tentativas_realizadas' => $dadosAntes['tentativas'] ?? 0,
                'cancelado_por' => $canceladoPor,
                'motivo' => $motivo,
                'tipo_cancelamento' => $tipo,
                'ip' => RequestHelper::getClientIp(),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_assignments',
            $assignmentId,
            $canceladoPor,
            'CANCELAMENTO',
            $dadosAntes,
            [
                'status' => 'cancelado',
                'cancelado_em' => date('Y-m-d H:i:s'),
                'motivo_cancelamento' => $motivo
            ]
        );
    }

    /**
     * Log de visualização de resultado
     */
    public static function logResultViewed(int $attemptId, int $userId): void
    {
        GenerateLog::generateLog("debug", "Resultado de avaliação visualizado", [
            'attempt_id' => $attemptId,
            'user_id' => $userId,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log de resposta individual
     */
    public static function logAnswerSaved(int $answerId, array $data, int $userId): void
    {
        GenerateLog::generateLog("debug", "Resposta de avaliação salva", [
            'answer_id' => $answerId,
            'user_id' => $userId,
            'question_id' => $data['evaluation_question_id'] ?? null,
            'correta' => isset($data['pontuacao']) && $data['pontuacao'] > 0,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log de erro/exceção
     */
    public static function logError(string $operacao, \Exception $e, array $contexto = []): void
    {
        GenerateLog::generateLog("error", "ERRO no módulo de avaliações - $operacao", [
            'operacao' => $operacao,
            'erro_mensagem' => $e->getMessage(),
            'erro_arquivo' => $e->getFile(),
            'erro_linha' => $e->getLine(),
            'contexto' => $contexto,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s'),
            'stack_trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Log de notificação enviada
     */
    public static function logNotificationSent(
        int $userId, 
        int $modelId, 
        string $tipo, 
        bool $sucesso,
        ?string $erro = null
    ): void {
        GenerateLog::generateLog(
            $sucesso ? "info" : "error",
            "Notificação de avaliação " . ($sucesso ? "enviada" : "FALHOU"),
            [
                'user_id' => $userId,
                'model_id' => $modelId,
                'tipo_notificacao' => $tipo,
                'sucesso' => $sucesso,
                'erro' => $erro,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * Log de mudança de status de modelo
     */
    public static function logModelStatusChanged(
        int $modelId, 
        string $titulo,
        int $statusAnterior, 
        int $statusNovo, 
        int $userId
    ): void {
        $acao = $statusNovo == 1 ? "ATIVADO" : "DESATIVADO";
        
        GenerateLog::generateLog("notice", "Modelo de avaliação $acao", [
            'model_id' => $modelId,
            'titulo' => $titulo,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'alterado_por' => $userId,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        LogAlteracaoService::registrarAlteracao(
            'adms_evaluation_models',
            $modelId,
            $userId,
            'UPDATE',
            ['ativo' => $statusAnterior],
            ['ativo' => $statusNovo]
        );
    }

    /**
     * Log de geração de PDF
     */
    public static function logPdfGenerated(
        int $attemptId, 
        int $userId, 
        string $fileName, 
        int $generatedBy
    ): void {
        GenerateLog::generateLog("info", "PDF de avaliação gerado", [
            'attempt_id' => $attemptId,
            'user_id' => $userId,
            'file_name' => $fileName,
            'generated_by' => $generatedBy,
            'ip' => RequestHelper::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

