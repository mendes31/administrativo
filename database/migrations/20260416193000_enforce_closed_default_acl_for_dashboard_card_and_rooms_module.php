<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Garante que o card "Meu calendário" e o módulo "Reserva de Salas"
 * não fiquem autorizados por padrão na matriz de permissões.
 */
final class EnforceClosedDefaultAclForDashboardCardAndRoomsModule extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        // Evita autorização implícita para novos níveis.
        $this->execute(
            "UPDATE adms_pages
             SET default_page = 0, updated_at = NOW()
             WHERE controller = 'DashboardCardMyCalendar'
                OR adms_groups_page_id IN (
                    SELECT id FROM adms_groups_pages WHERE name = 'Reserva de Salas'
                )"
        );

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        // Normaliza ACL atual: páginas privadas do escopo começam fechadas (permission=0).
        $this->execute(
            "UPDATE adms_access_levels_pages alp
             INNER JOIN adms_pages p ON p.id = alp.adms_page_id
             LEFT JOIN adms_groups_pages gp ON gp.id = p.adms_groups_page_id
             SET alp.permission = 0,
                 alp.updated_at = NOW()
             WHERE p.public_page = 0
               AND (
                    p.controller = 'DashboardCardMyCalendar'
                    OR gp.name = 'Reserva de Salas'
               )"
        );
    }

    public function down(): void
    {
        // Sem rollback automático seguro:
        // não é possível inferir quais permissões estavam autorizadas antes.
    }
}

