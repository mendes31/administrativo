<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Nenhuma página de gamificação deve nascer como padrão (default_page = 0).
 */
final class EnforceClosedDefaultPageForGamification extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0,
                 updated_at = NOW()
             WHERE directory = 'gamification'"
        );
    }

    public function down(): void
    {
        // Sem rollback automático seguro.
    }
}

