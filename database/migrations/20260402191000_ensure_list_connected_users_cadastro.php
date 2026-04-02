<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Normaliza o cadastro em adms_pages para ListConnectedUsers:
 * - directory = 'logs' (obrigatório para PSR-4: App\adms\Controllers\logs\...)
 * - controller / controller_url alinhados ao SlugController
 * - mesmo pacote (adms_packages_page_id = 1) e grupo Logs que as demais páginas de log
 *
 * Idempotente: pode rodar várias vezes em produção após ajustes manuais incorretos.
 */
final class EnsureListConnectedUsersCadastro extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $refPkg = $this->fetchRow("SELECT adms_packages_page_id FROM adms_pages WHERE controller = 'ListLogAcessos' LIMIT 1");
        $packageId = $refPkg ? (int)$refPkg['adms_packages_page_id'] : 1;

        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Logs' LIMIT 1");
        $groupId = $g ? (int)$g['id'] : null;

        $groupSql = $groupId !== null ? ', adms_groups_page_id = ' . $groupId : '';

        $this->execute(
            "UPDATE adms_pages SET
                name = 'Usuários conectados',
                controller = 'ListConnectedUsers',
                controller_url = 'list-connected-users',
                directory = 'logs',
                obs = 'Lista sessões ativas (usuários conectados) a partir de adms_sessions.',
                public_page = 0,
                page_status = 1,
                adms_packages_page_id = {$packageId},
                updated_at = NOW()
                {$groupSql}
             WHERE controller_url = 'list-connected-users'
                OR controller = 'ListConnectedUsers'"
        );
    }

    public function down(): void
    {
        // Dados de correção; sem reversão.
    }
}
