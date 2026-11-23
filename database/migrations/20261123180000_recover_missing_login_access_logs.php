<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Recupera LOGIN de quem autenticou sem linha em adms_log_acessos.
 *
 * O fluxo de troca obrigatória de senha gravava a sessão e não gravava LOGIN.
 * Quem ainda tem sessão, ou outro evento de acesso, deixa de aparecer como
 * "Nunca acessou". Quem não tem sessão nem log não pode ser recuperado.
 */
final class RecoverMissingLoginAccessLogs extends AbstractMigration
{
    private const DETALHE = 'LOGIN recuperado: acesso existia sem registro de LOGIN.';

    public function up(): void
    {
        if (!$this->hasTable('adms_log_acessos') || !$this->hasTable('adms_users')) {
            return;
        }

        $hasSessions = $this->hasTable('adms_sessions');
        $sessionJoin = $hasSessions
            ? 'LEFT JOIN (
                    SELECT user_id, MAX(created_at) AS created_at
                    FROM adms_sessions
                    GROUP BY user_id
               ) sess ON sess.user_id = u.id'
            : 'LEFT JOIN (
                    SELECT NULL AS user_id, NULL AS created_at
               ) sess ON 1 = 0';

        $this->execute(
            'INSERT INTO adms_log_acessos
                (usuario_id, tipo_acesso, ip, hostname, user_agent, data_acesso, detalhes, criado_por)
             SELECT
                u.id,
                \'LOGIN\',
                CASE
                    WHEN sess.created_at IS NOT NULL
                         AND (logmax.data_acesso IS NULL OR sess.created_at >= logmax.data_acesso)
                        THEN \'0.0.0.0\'
                    ELSE COALESCE(NULLIF(logrow.ip, \'\'), \'0.0.0.0\')
                END,
                CASE
                    WHEN sess.created_at IS NOT NULL
                         AND (logmax.data_acesso IS NULL OR sess.created_at >= logmax.data_acesso)
                        THEN NULL
                    ELSE LEFT(logrow.hostname, 255)
                END,
                CASE
                    WHEN sess.created_at IS NOT NULL
                         AND (logmax.data_acesso IS NULL OR sess.created_at >= logmax.data_acesso)
                        THEN NULL
                    ELSE LEFT(logrow.user_agent, 255)
                END,
                CASE
                    WHEN sess.created_at IS NOT NULL
                         AND (logmax.data_acesso IS NULL OR sess.created_at >= logmax.data_acesso)
                        THEN sess.created_at
                    ELSE logmax.data_acesso
                END,
                ' . $this->quote(self::DETALHE) . ',
                u.id
             FROM adms_users u
             ' . $sessionJoin . '
             LEFT JOIN (
                SELECT usuario_id, MAX(data_acesso) AS data_acesso
                FROM adms_log_acessos
                GROUP BY usuario_id
             ) logmax ON logmax.usuario_id = u.id
             LEFT JOIN adms_log_acessos logrow
                ON logrow.usuario_id = logmax.usuario_id
               AND logrow.data_acesso = logmax.data_acesso
               AND logrow.id = (
                    SELECT MAX(l2.id)
                    FROM adms_log_acessos l2
                    WHERE l2.usuario_id = logmax.usuario_id
                      AND l2.data_acesso = logmax.data_acesso
               )
             WHERE NOT EXISTS (
                SELECT 1
                FROM adms_log_acessos login_ok
                WHERE login_ok.usuario_id = u.id
                  AND login_ok.tipo_acesso = \'LOGIN\'
             )
             AND (sess.created_at IS NOT NULL OR logmax.data_acesso IS NOT NULL)'
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_log_acessos')) {
            return;
        }

        $this->execute(
            'DELETE FROM adms_log_acessos
             WHERE tipo_acesso = \'LOGIN\'
               AND detalhes = ' . $this->quote(self::DETALHE)
        );
    }

    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
