<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Monta evidências técnicas do canal sem descriptografar ou exportar conteúdo sensível.
 */
final class WhistleblowingEvidenceRepository extends DbConnection
{
    /**
     * @return array<string, mixed>|null
     */
    public function buildPackage(string $protocol = ''): ?array
    {
        $protocol = strtoupper(trim($protocol));
        $report = $protocol !== '' ? $this->safeReport($protocol) : null;
        if ($protocol !== '' && $report === null) {
            return null;
        }

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'environment' => (string) ($_ENV['APP_ENV'] ?? 'não informado'),
            'protocol' => $protocol,
            'report' => $report,
            'cryptography' => $protocol !== '' ? $this->cryptographyEvidence($protocol) : null,
            'access_summary' => $protocol !== '' ? $this->accessSummary($protocol) : [],
            'status_timeline' => $protocol !== '' ? $this->statusTimeline($protocol) : [],
            'messages_summary' => $protocol !== '' ? $this->messagesSummary($protocol) : [],
            'attachments_summary' => $protocol !== '' ? $this->attachmentsSummary($protocol) : [],
            'committees' => $this->committeesSummary(),
            'committee_categories' => $this->committeeCategories(),
            'policies' => $this->safePolicies(),
            'retention_runs' => $this->retentionRuns(),
            'operational_summary' => $this->operationalSummary(),
            'retention_summary' => $this->retentionSummary(),
            'schema' => $this->schemaEvidence(),
        ];
    }

    public function recordExport(int $userId, ?int $reportId, string $format, string $protocol): void
    {
        if ($userId <= 0 || !$this->tableExists('adms_whistleblowing_evidence_exports')) {
            return;
        }

        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_whistleblowing_evidence_exports
                (user_id, report_id, format, protocol_reference, generated_at)
             VALUES (:user_id, :report_id, :format, :protocol, :generated_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':report_id' => $reportId !== null && $reportId > 0 ? $reportId : null,
            ':format' => substr(strtolower($format), 0, 10),
            ':protocol' => $protocol !== '' ? substr($protocol, 0, 32) : null,
            ':generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findReportIdByProtocol(string $protocol): ?int
    {
        $protocol = strtoupper(trim($protocol));
        if ($protocol === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_whistleblowing_reports WHERE protocol = :protocol LIMIT 1'
        );
        $stmt->execute([':protocol' => $protocol]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentExports(int $limit = 10): array
    {
        if (!$this->tableExists('adms_whistleblowing_evidence_exports')) {
            return [];
        }
        $limit = max(1, min(50, $limit));
        $sql = "SELECT format, protocol_reference, generated_at
                FROM adms_whistleblowing_evidence_exports
                ORDER BY generated_at DESC
                LIMIT {$limit}";

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeReport(string $protocol): ?array
    {
        $sql = "SELECT r.protocol,
                       r.category AS classificacao,
                       r.risk_level AS risco,
                       r.status,
                       CASE WHEN r.is_reporter_identified = 1 THEN 'Sim' ELSE 'Não' END AS identificacao_voluntaria,
                       c.name AS comite,
                       CASE WHEN r.assigned_user_id IS NULL THEN 'Não' ELSE 'Sim' END AS possui_responsavel,
                       r.sla_response_deadline AS prazo_primeira_resposta,
                       r.first_response_at AS primeira_resposta_em,
                       r.sla_closure_started_at AS inicio_sla_encerramento,
                       r.sla_closure_deadline AS prazo_encerramento,
                       r.reporter_response_requested_at AS retorno_solicitado_em,
                       r.reporter_response_deadline AS prazo_retorno_denunciante,
                       r.reporter_inactivity_notified_at AS alerta_inatividade_em,
                       r.created_at AS registrada_em,
                       r.closed_at AS encerrada_em,
                       r.archived_at AS arquivada_em,
                       r.retention_archive_at AS previsao_arquivamento,
                       r.retention_delete_at AS previsao_exclusao
                FROM adms_whistleblowing_reports r
                LEFT JOIN adms_whistleblowing_committees c ON c.id = r.committee_id
                WHERE r.protocol = :protocol
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cryptographyEvidence(string $protocol): ?array
    {
        $sql = "SELECT
                    CASE WHEN content_encrypted IS NOT NULL AND CHAR_LENGTH(content_encrypted) > 0
                        THEN 'Cifrado em repouso' ELSE 'Ausente' END AS relato,
                    CHAR_LENGTH(content_encrypted) AS tamanho_relato_cifrado,
                    CASE WHEN reporter_contact_encrypted IS NULL
                        THEN 'Não armazenado' ELSE 'Cifrado em repouso' END AS contato_voluntario,
                    COALESCE(CHAR_LENGTH(reporter_contact_encrypted), 0) AS tamanho_contato_cifrado,
                    CASE WHEN password_hash LIKE '\$2y\$%' OR password_hash LIKE '\$argon%'
                        THEN 'Hash forte' ELSE 'Hash presente' END AS senha_acesso,
                    CHAR_LENGTH(password_hash) AS tamanho_hash_senha,
                    CASE WHEN closure_reason_encrypted IS NULL
                        THEN 'Não armazenado' ELSE 'Cifrado em repouso' END AS motivo_encerramento
                FROM adms_whistleblowing_reports
                WHERE protocol = :protocol LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function accessSummary(string $protocol): array
    {
        $sql = 'SELECT al.action AS acao, COUNT(*) AS quantidade,
                       MIN(al.created_at) AS primeiro_registro,
                       MAX(al.created_at) AS ultimo_registro
                FROM adms_whistleblowing_access_log al
                INNER JOIN adms_whistleblowing_reports r ON r.id = al.report_id
                WHERE r.protocol = :protocol
                GROUP BY al.action
                ORDER BY al.action';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statusTimeline(string $protocol): array
    {
        $sql = "SELECT sl.from_status AS status_anterior,
                       sl.to_status AS status_novo,
                       CASE WHEN sl.user_id IS NULL THEN 'Canal público' ELSE 'Usuário interno autenticado' END AS origem,
                       CASE WHEN sl.notes_encrypted IS NULL OR sl.notes_encrypted = ''
                            THEN 'Sem observação' ELSE 'Observação cifrada' END AS observacao,
                       sl.created_at AS registrada_em
                FROM adms_whistleblowing_status_log sl
                INNER JOIN adms_whistleblowing_reports r ON r.id = sl.report_id
                WHERE r.protocol = :protocol
                ORDER BY sl.created_at ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagesSummary(string $protocol): array
    {
        $sql = "SELECT m.sender_type AS remetente,
                       CASE WHEN m.is_internal_note = 1 THEN 'Nota interna' ELSE 'Mensagem pública' END AS visibilidade,
                       COUNT(*) AS quantidade,
                       MIN(m.created_at) AS primeira_mensagem,
                       MAX(m.created_at) AS ultima_mensagem,
                       'Conteúdo cifrado e omitido' AS conteudo
                FROM adms_whistleblowing_messages m
                INNER JOIN adms_whistleblowing_reports r ON r.id = m.report_id
                WHERE r.protocol = :protocol
                GROUP BY m.sender_type, m.is_internal_note
                ORDER BY m.sender_type, m.is_internal_note";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attachmentsSummary(string $protocol): array
    {
        $sql = "SELECT a.uploaded_by AS enviado_por,
                       CASE WHEN a.stored_name LIKE '%.enc' THEN 'Cifrado em disco' ELSE 'Legado' END AS protecao,
                       COUNT(*) AS quantidade,
                       SUM(a.size_bytes) AS total_bytes,
                       MIN(a.created_at) AS primeiro_anexo,
                       MAX(a.created_at) AS ultimo_anexo
                FROM adms_whistleblowing_attachments a
                INNER JOIN adms_whistleblowing_reports r ON r.id = a.report_id
                WHERE r.protocol = :protocol
                GROUP BY a.uploaded_by,
                         CASE WHEN a.stored_name LIKE '%.enc' THEN 'Cifrado em disco' ELSE 'Legado' END
                ORDER BY a.uploaded_by, protecao";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':protocol' => $protocol]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function committeesSummary(): array
    {
        $sql = 'SELECT c.name AS comite,
                       CASE WHEN c.is_active = 1 THEN \'Ativo\' ELSE \'Inativo\' END AS situacao,
                       COUNT(DISTINCT cm.user_id) AS quantidade_membros,
                       COUNT(DISTINCT cc.category) AS quantidade_classificacoes
                FROM adms_whistleblowing_committees c
                LEFT JOIN adms_whistleblowing_committee_members cm ON cm.committee_id = c.id
                LEFT JOIN adms_whistleblowing_committee_categories cc ON cc.committee_id = c.id
                GROUP BY c.id, c.name, c.is_active
                ORDER BY c.name';

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function committeeCategories(): array
    {
        $sql = 'SELECT c.name AS comite, cc.category AS classificacao
                FROM adms_whistleblowing_committee_categories cc
                INNER JOIN adms_whistleblowing_committees c ON c.id = cc.committee_id
                WHERE c.is_active = 1
                ORDER BY c.name, cc.category';

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    private function safePolicies(): array
    {
        $sql = 'SELECT retention_archive_years AS retencao_arquivamento_anos,
                       retention_delete_years AS retencao_exclusao_anos,
                       cron_enabled AS rotina_automatica_ativa,
                       rate_limit_max_attempts AS tentativas_acompanhamento,
                       rate_limit_window_minutes AS janela_bloqueio_minutos,
                       sla_first_response_hours AS sla_primeira_resposta_horas,
                       sla_closure_enabled AS sla_encerramento_ativo,
                       sla_closure_hours AS sla_encerramento_horas,
                       reporter_inactivity_enabled AS prazo_retorno_ativo,
                       reporter_inactivity_days AS prazo_retorno_dias,
                       captcha_enabled AS captcha_ativo,
                       captcha_provider AS provedor_captcha,
                       updated_at AS atualizado_em
                FROM adms_whistleblowing_config
                ORDER BY id ASC LIMIT 1';
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function retentionRuns(): array
    {
        if (!$this->tableExists('adms_whistleblowing_retention_runs')) {
            return [];
        }
        $sql = 'SELECT started_at AS iniciada_em,
                       finished_at AS finalizada_em,
                       status,
                       triggered_by AS acionada_por,
                       archived_count AS arquivadas,
                       deleted_count AS excluidas,
                       attachments_deleted AS anexos_excluidos,
                       duration_ms AS duracao_ms
                FROM adms_whistleblowing_retention_runs
                ORDER BY id DESC LIMIT 10';

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    private function operationalSummary(): array
    {
        $sql = "SELECT COUNT(*) AS total_denuncias,
                       SUM(CASE WHEN status = 'Encerrada' THEN 1 ELSE 0 END) AS encerradas,
                       SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) AS arquivadas,
                       SUM(CASE WHEN is_reporter_identified = 1 THEN 1 ELSE 0 END) AS identificacao_voluntaria,
                       SUM(CASE WHEN is_reporter_identified = 0 OR is_reporter_identified IS NULL THEN 1 ELSE 0 END) AS anonimas
                FROM adms_whistleblowing_reports";
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    private function retentionSummary(): array
    {
        $sql = "SELECT COUNT(*) AS encerradas_aguardando_retencao,
                       MIN(retention_archive_at) AS proximo_arquivamento,
                       MIN(retention_delete_at) AS proxima_exclusao,
                       MAX(closed_at) AS ultimo_encerramento
                FROM adms_whistleblowing_reports
                WHERE status = 'Encerrada'
                  AND closed_at IS NOT NULL
                  AND archived_at IS NULL";
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function schemaEvidence(): array
    {
        $tables = [
            'adms_whistleblowing_reports',
            'adms_whistleblowing_access_log',
            'adms_whistleblowing_status_log',
            'adms_whistleblowing_messages',
            'adms_whistleblowing_attachments',
        ];
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $sql = "SELECT TABLE_NAME AS tabela,
                       COLUMN_NAME AS coluna,
                       COLUMN_TYPE AS tipo,
                       IS_NULLABLE AS aceita_nulo,
                       COLUMN_KEY AS chave
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME IN ({$placeholders})
                ORDER BY TABLE_NAME, ORDINAL_POSITION";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($tables);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
        );
        $stmt->execute([':table' => $table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
