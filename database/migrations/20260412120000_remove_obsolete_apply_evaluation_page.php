<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Remove a página obsoleta ApplyEvaluation (apply-evaluation) substituída por AssignEvaluation.
 *
 * Critério: controller ApplyEvaluation em evaluations com nome ou obs marcados como OBSOLETO
 * (não remove outras linhas ApplyEvaluation que possam existir sem essa marcação).
 */
final class RemoveObsoleteApplyEvaluationPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $filter = "(controller = 'ApplyEvaluation'
            AND directory = 'evaluations'
            AND (
                name LIKE '%OBSOLETO%'
                OR obs LIKE 'OBSOLETO:%'
            ))";

        $this->execute(
            "DELETE FROM adms_access_levels_pages
             WHERE adms_page_id IN (SELECT id FROM (SELECT id FROM adms_pages WHERE {$filter}) t)"
        );

        if ($this->hasTable('adms_access_levels_pages_departments')) {
            $this->execute(
                "DELETE FROM adms_access_levels_pages_departments
                 WHERE adms_page_id IN (SELECT id FROM (SELECT id FROM adms_pages WHERE {$filter}) t)"
            );
        }

        if ($this->hasTable('adms_access_levels_pages_branches')) {
            $this->execute(
                "DELETE FROM adms_access_levels_pages_branches
                 WHERE adms_page_id IN (SELECT id FROM (SELECT id FROM adms_pages WHERE {$filter}) t)"
            );
        }

        if ($this->hasTable('adms_access_levels_pages_branch_departments')) {
            $this->execute(
                "DELETE FROM adms_access_levels_pages_branch_departments
                 WHERE adms_page_id IN (SELECT id FROM (SELECT id FROM adms_pages WHERE {$filter}) t)"
            );
        }

        $this->execute("DELETE FROM adms_pages WHERE {$filter}");
    }

    public function down(): void
    {
        // Irreversível: não recriar registo obsoleto.
    }
}
