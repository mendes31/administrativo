<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante directory/controller corretos para o autoload PSR-4 em Linux (case-sensitive).
 * Caminho esperado: \App\adms\Controllers\logs\ListConnectedUsers → app/adms/Controllers/logs/
 */
final class FixListConnectedUsersDirectory extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "UPDATE adms_pages
             SET directory = 'logs',
                 controller = 'ListConnectedUsers',
                 controller_url = 'list-connected-users',
                 updated_at = NOW()
             WHERE controller_url = 'list-connected-users'"
        );
    }

    public function down(): void
    {
        // Sem reversão: apenas normalização de dados.
    }
}
