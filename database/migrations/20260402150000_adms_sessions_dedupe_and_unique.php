<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Remove duplicatas (user_id + session_id), índice único e suporte a DELETE em vez de status invalidada.
 */
final class AdmsSessionsDedupeAndUnique extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sessions')) {
            return;
        }

        // 1) Remover duplicatas, mantendo o maior id por par (user_id, session_id)
        $this->execute(
            'DELETE t FROM adms_sessions t
             INNER JOIN adms_sessions t2
               ON t.user_id = t2.user_id
              AND t.session_id = t2.session_id
              AND t.id < t2.id'
        );

        // 2) Índice único (user_id + session_id). Prefixo em session_id: utf8mb4 × 255 excede 767 bytes
        // em MySQL com limite antigo; (user_id 4B + session_id(190) 760B) = 764B ≤ 767B.
        $sm = $this->fetchRow(
            "SELECT COUNT(*) AS c FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'adms_sessions'
               AND index_name = 'uq_adms_sessions_user_session'"
        );
        if ((int)($sm['c'] ?? 0) === 0) {
            $this->execute(
                'ALTER TABLE adms_sessions
                 ADD UNIQUE INDEX uq_adms_sessions_user_session (user_id, session_id(190))'
            );
        }

        // 3) Remover linhas antigas já marcadas como invalidada (liberar espaço)
        $table = $this->table('adms_sessions');
        if ($table->hasColumn('status')) {
            $this->execute("DELETE FROM adms_sessions WHERE status = 'invalidada'");
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sessions')) {
            return;
        }
        $sm = $this->fetchRow(
            "SELECT COUNT(*) AS c FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'adms_sessions'
               AND index_name = 'uq_adms_sessions_user_session'"
        );
        if ((int)($sm['c'] ?? 0) > 0) {
            $this->execute('ALTER TABLE adms_sessions DROP INDEX uq_adms_sessions_user_session');
        }
    }
}
