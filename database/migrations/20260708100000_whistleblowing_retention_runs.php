<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Execuções da rotina de retenção LGPD do Canal de Denúncias.
 *
 * Mantém evidência de arquivamentos/expurgos para auditoria.
 */
final class WhistleblowingRetentionRuns extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_whistleblowing_retention_runs')) {
            return;
        }

        $this->table('adms_whistleblowing_retention_runs')
            ->addColumn('started_at', 'datetime', ['null' => false])
            ->addColumn('finished_at', 'datetime', ['null' => true])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'running',
                'comment' => 'running|success|error',
            ])
            ->addColumn('archived_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('deleted_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('attachments_deleted', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('duration_ms', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('triggered_by', 'string', [
                'limit' => 20,
                'default' => 'cron',
                'comment' => 'cron|manual',
            ])
            ->addColumn('message', 'text', ['null' => true])
            ->addIndex(['started_at'])
            ->addIndex(['status', 'finished_at'])
            ->create();
    }
}

