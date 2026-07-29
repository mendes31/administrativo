<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand — cisão leve ACL: Estoque e CRM (Fase D / P2).
 *
 * - Cria subgrupos por nome (idempotente via AdmsPageGroupSplit).
 * - Reatribui adms_pages.adms_groups_page_id.
 * - NÃO altera adms_access_levels_pages.
 * - Menu lateral não depende destes grupos.
 *
 * @see docs/05_AUTORIZACAO/PLANO_SEPARACAO_GRUPOS_PAGINAS.md
 */
final class SplitEstoqueCrmPageGroups extends AbstractMigration
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

        $now = date('Y-m-d H:i:s');
        $parents = [
            'Estoque' => ['Estoque -%'],
            'CRM' => ['CRM -%'],
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
