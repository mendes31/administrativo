<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fecha ACL das páginas de gamificação para níveis de acesso (permission = 0).
 * Super Administrador e Super Usuário continuam com acesso total via regra de negócio.
 */
final class EnforceClosedAclForGamificationPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.directory = 'gamification'"
        );

        // Garantia adicional para o card de dashboard ligado a quizzes.
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.controller = 'DashboardCardGamificationQuizzes'"
        );
    }

    public function down(): void
    {
        // Sem rollback automático seguro.
    }
}

