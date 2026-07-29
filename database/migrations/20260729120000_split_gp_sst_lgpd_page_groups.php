<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand — cisão dos mega-grupos ACL: Gestão de Pessoas, SST e LGPD.
 *
 * - Cria novos adms_groups_pages por nome (idempotente).
 * - Reatribui adms_pages.adms_groups_page_id.
 * - NÃO altera adms_access_levels_pages (permissões por page_id preservadas).
 * - Menu lateral não depende destes grupos.
 *
 * @see docs/05_AUTORIZACAO/PROPOSTA_NOVOS_GRUPOS_ACL.md
 */
final class SplitGpSstLgpdPageGroups extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        require_once dirname(__DIR__) . '/helpers/AdmsPageGroupSplit.php';

        $now = date('Y-m-d H:i:s');
        $fetchRow = fn (string $sql) => $this->fetchRow($sql);
        $fetchAll = fn (string $sql) => $this->fetchAll($sql);
        $execute = fn (string $sql) => $this->execute($sql);

        AdmsPageGroupSplit::reassignPages($fetchRow, $fetchAll, $execute, $now);
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        require_once dirname(__DIR__) . '/helpers/AdmsPageGroupSplit.php';

        $now = date('Y-m-d H:i:s');
        $parents = [
            'Gestão de Pessoas' => ['Gestão de Pessoas -%', 'GP -%', 'GP —%', 'GP—%'],
            'Segurança e Medicina' => ['SST -%', 'SST —%', 'SST—%'],
            'LGPD' => ['LGPD -%', 'LGPD —%', 'LGPD—%'],
        ];

        foreach ($parents as $parentName => $prefixes) {
            $parent = $this->fetchRow(
                'SELECT id FROM adms_groups_pages WHERE name = '
                . $this->quote($parentName) . ' LIMIT 1'
            );
            if (!$parent) {
                continue;
            }
            $parentId = (int) $parent['id'];
            foreach ($prefixes as $prefix) {
                $prefixSql = $this->quote($prefix);
                $this->execute(
                    "UPDATE adms_pages p
                     INNER JOIN adms_groups_pages g ON g.id = p.adms_groups_page_id
                     SET p.adms_groups_page_id = {$parentId}, p.updated_at = " . $this->quote($now) . "
                     WHERE g.name LIKE {$prefixSql}"
                );
            }
        }
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
