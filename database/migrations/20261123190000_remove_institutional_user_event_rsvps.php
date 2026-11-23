<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * A conta manager não confirma presença. Remove respostas já gravadas
 * para ela, inclusive a recusa automática depois do prazo.
 */
final class RemoveInstitutionalUserEventRsvps extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_company_event_rsvps') || !$this->hasTable('adms_users')) {
            return;
        }

        $where = "LOWER(TRIM(u.username)) = 'manager' OR TRIM(u.name) = 'Grupo Tiaraju'";

        if ($this->hasTable('adms_company_event_guests')) {
            $this->execute(
                "DELETE g FROM adms_company_event_guests g
                 INNER JOIN adms_company_event_rsvps r ON r.id = g.rsvp_id
                 INNER JOIN adms_users u ON u.id = r.user_id
                 WHERE {$where}"
            );
        }

        $this->execute(
            "DELETE r FROM adms_company_event_rsvps r
             INNER JOIN adms_users u ON u.id = r.user_id
             WHERE {$where}"
        );
    }

    public function down(): void
    {
    }
}
