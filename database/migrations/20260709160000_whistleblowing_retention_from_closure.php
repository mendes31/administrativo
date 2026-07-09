<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Retenção LGPD: prazos contam da data de encerramento (closed_at), não da abertura.
 */
final class WhistleblowingRetentionFromClosure extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_reports')) {
            return;
        }

        // Denúncias encerradas sem closed_at: usa último log de status "Encerrada".
        if ($this->hasTable('adms_whistleblowing_status_log')) {
            $rows = $this->fetchAll(
                "SELECT r.id
                 FROM adms_whistleblowing_reports r
                 WHERE r.status = 'Encerrada' AND r.closed_at IS NULL"
            );
            foreach ($rows as $row) {
                $reportId = (int) ($row['id'] ?? 0);
                if ($reportId <= 0) {
                    continue;
                }
                $log = $this->fetchRow(
                    'SELECT created_at FROM adms_whistleblowing_status_log
                     WHERE report_id = ' . $reportId . " AND to_status = 'Encerrada'
                     ORDER BY created_at DESC LIMIT 1"
                );
                $closedAt = $log['created_at'] ?? null;
                if ($closedAt === null) {
                    $fallback = $this->fetchRow(
                        'SELECT updated_at FROM adms_whistleblowing_reports WHERE id = ' . $reportId . ' LIMIT 1'
                    );
                    $closedAt = $fallback['updated_at'] ?? date('Y-m-d H:i:s');
                }
                $quoted = $this->getAdapter()->getConnection()->quote((string) $closedAt);
                $this->execute(
                    'UPDATE adms_whistleblowing_reports SET closed_at = ' . $quoted
                    . ' WHERE id = ' . $reportId . ' AND closed_at IS NULL'
                );
            }
        }

        // Abertas: remove prazos pré-calculados (só passam a contar no encerramento).
        $this->execute(
            "UPDATE adms_whistleblowing_reports
             SET retention_archive_at = NULL, retention_delete_at = NULL
             WHERE status != 'Encerrada' OR closed_at IS NULL"
        );

        $archiveYears = 5;
        $deleteYears = 10;
        if ($this->hasTable('adms_whistleblowing_config')) {
            $cfg = $this->fetchRow('SELECT retention_archive_years, retention_delete_years FROM adms_whistleblowing_config LIMIT 1');
            if ($cfg) {
                $archiveYears = max(1, (int) ($cfg['retention_archive_years'] ?? 5));
                $deleteYears = max($archiveYears + 1, (int) ($cfg['retention_delete_years'] ?? 10));
            }
        }

        $this->execute(
            "UPDATE adms_whistleblowing_reports
             SET retention_archive_at = DATE_ADD(closed_at, INTERVAL {$archiveYears} YEAR),
                 retention_delete_at = DATE_ADD(closed_at, INTERVAL {$deleteYears} YEAR),
                 updated_at = NOW()
             WHERE status = 'Encerrada'
               AND closed_at IS NOT NULL
               AND archived_at IS NULL"
        );
    }

    public function down(): void
    {
        // Sem reversão automática — política de negócio alterada.
    }
}
