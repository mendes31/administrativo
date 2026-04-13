<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alinha política de ACL: páginas privadas do CRUD de turnos de trabalho devem iniciar com permission = 0
 * em todos os níveis (ambientes que já aplicaram {@see CreateAdmsWorkShiftsAndPages} com cópia a partir de ListDepartments).
 */
final class ResetWorkShiftsPagesPermissionsToZero extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $controllers = [
            'ListWorkShifts',
            'CreateWorkShift',
            'ViewWorkShift',
            'UpdateWorkShift',
            'DeleteWorkShift',
        ];
        $ids = [];
        foreach ($controllers as $c) {
            $row = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $this->q($c) . ' LIMIT 1');
            if ($row) {
                $ids[] = (int) $row['id'];
            }
        }
        if ($ids === []) {
            return;
        }
        $in = implode(',', $ids);
        $this->execute(
            "UPDATE adms_access_levels_pages SET permission = 0, updated_at = NOW() WHERE adms_page_id IN ({$in})"
        );
    }

    public function down(): void
    {
        // Irreversível sem histórico de valores anteriores.
    }

    private function q(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
