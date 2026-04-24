<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Projeto padronizado por seed para cadastro de páginas.
 * Esta migration permanece apenas como blindagem para ambientes legados.
 */
final class RegisterDashboardCardQuizzesAndLeaderboard extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        // Apenas normaliza páginas, caso já existam via seed.
        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = NOW()
             WHERE controller IN ('DashboardCardGamificationQuizzes', 'GamificationLeaderboard')"
        );

        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute(
                "UPDATE adms_access_levels_pages alp
                 INNER JOIN adms_pages p ON p.id = alp.adms_page_id
                 SET alp.permission = 0, alp.updated_at = NOW()
                 WHERE p.controller IN ('DashboardCardGamificationQuizzes', 'GamificationLeaderboard')
                   AND p.public_page = 0"
            );
        }
    }

    public function down(): void
    {
        // Sem rollback destrutivo de páginas (cadastro oficial está na seed).
    }
}
