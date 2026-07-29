<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Expand — cisão ACL: Comunicação Social (Fase / P3).
 *
 * Timeline / Gamificação / Eventos.
 * Não altera adms_access_levels_pages. Menu lateral intacto.
 *
 * @see docs/05_AUTORIZACAO/PLANO_SEPARACAO_GRUPOS_PAGINAS.md
 */
final class SplitComunicacaoSocialPageGroups extends AbstractMigration
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
        $parent = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = '
            . $this->quote('Comunicação Social') . ' LIMIT 1'
        );
        if (!$parent) {
            return;
        }
        $parentId = (int) $parent['id'];
        $this->execute(
            "UPDATE adms_pages p
             INNER JOIN adms_groups_pages g ON g.id = p.adms_groups_page_id
             SET p.adms_groups_page_id = {$parentId}, p.updated_at = " . $this->quote($now) . "
             WHERE g.name LIKE " . $this->quote('Comunicação Social -%')
        );
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
