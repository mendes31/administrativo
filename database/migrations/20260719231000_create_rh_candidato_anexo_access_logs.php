<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Log append-only de download de anexos/currículos (LGPD / accountability).
 * Sem FK destrutiva: evidência sobrevive à exclusão/anonimização do candidato.
 */
final class CreateRhCandidatoAnexoAccessLogs extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_candidato_anexo_access_logs')) {
            return;
        }

        $this->table('rh_candidato_anexo_access_logs', ['id' => true, 'primary_key' => ['id']])
            ->addColumn('rh_candidato_anexo_id', 'integer', [
                'signed' => false,
                'null' => true,
                'comment' => 'NULL se anexo não identificado (legado) ou já removido',
            ])
            ->addColumn('rh_candidato_id', 'integer', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('actor_user_id', 'integer', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('action', 'string', [
                'limit' => 32,
                'null' => false,
                'default' => 'download',
            ])
            ->addColumn('delivery_mode', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'inline',
                'comment' => 'inline | attachment',
            ])
            ->addColumn('source', 'string', [
                'limit' => 32,
                'null' => false,
                'comment' => 'authorized_controller | legacy_file_server',
            ])
            ->addColumn('anexo_tipo', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('path_hash', 'string', [
                'limit' => 64,
                'null' => true,
                'comment' => 'SHA-256 do caminho relativo (correlação sem PII de nome)',
            ])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['rh_candidato_id', 'created_at'], ['name' => 'idx_rh_cand_anexo_access_cand_time'])
            ->addIndex(['rh_candidato_anexo_id', 'created_at'], ['name' => 'idx_rh_cand_anexo_access_anexo_time'])
            ->addIndex(['actor_user_id', 'created_at'], ['name' => 'idx_rh_cand_anexo_access_actor_time'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidato_anexo_access_logs')) {
            $this->table('rh_candidato_anexo_access_logs')->drop()->save();
        }
    }
}
