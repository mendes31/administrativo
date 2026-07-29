<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige páginas SST do portal (EPI / treinamentos) que ficaram no grupo
 * "Gestão de Pessoas - Portal / Solicitações" após a cisão P1.
 * Não altera adms_access_levels_pages.
 */
final class ReassignSstPortalPagesFromGp extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        require_once dirname(__DIR__) . '/helpers/AdmsPageGroupSplit.php';

        $now = date('Y-m-d H:i:s');
        AdmsPageGroupSplit::reassignPages(
            fn (string $sql) => $this->fetchRow($sql),
            fn (string $sql) => $this->fetchAll($sql),
            fn (string $sql) => $this->execute($sql),
            $now
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $portal = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas - Portal / Solicitações' LIMIT 1"
        );
        if (!$portal) {
            return;
        }
        $portalId = (int) $portal['id'];
        $now = $this->quote(date('Y-m-d H:i:s'));
        $controllers = [
            'MyEpiDeliveries',
            'SignEpiFicha',
            'ViewEpiFichaPdf',
            'MySstTreinamentos',
            'ViewSstTreinamentoCertificadoPdf',
        ];
        foreach ($controllers as $controller) {
            $this->execute(
                'UPDATE adms_pages SET adms_groups_page_id = ' . $portalId
                . ', updated_at = ' . $now
                . ' WHERE controller = ' . $this->quote($controller)
            );
        }
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
