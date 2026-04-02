<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alinha "Visualizar Evento Corporativo" a ViewInformativo/ViewPolicy (página padrão do módulo).
 * Bancos que já rodaram a migration anterior com default_page = 0 precisam deste UPDATE.
 */
final class SetViewCompanyEventPageDefault extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_pages SET default_page = 1, updated_at = NOW() WHERE controller = 'ViewCompanyEvent' AND controller_url = 'view-company-event' LIMIT 1"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $this->execute(
            "UPDATE adms_pages SET default_page = 0, updated_at = NOW() WHERE controller = 'ViewCompanyEvent' AND controller_url = 'view-company-event' LIMIT 1"
        );
    }
}
