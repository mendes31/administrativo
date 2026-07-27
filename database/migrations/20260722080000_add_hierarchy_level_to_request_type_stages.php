<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Nível na hierarquia do solicitante por etapa (1=André, 2=Nathiele, …).
 */
final class AddHierarchyLevelToRequestTypeStages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_request_type_stages')) {
            return;
        }

        $table = $this->table('adms_request_type_stages');
        if (!$table->hasColumn('hierarchy_level')) {
            $table
                ->addColumn('hierarchy_level', 'integer', [
                    'signed' => false,
                    'default' => 1,
                    'after' => 'approver_kind',
                    'comment' => 'Para immediate: 1=gestor do solicitante, 2=chefe do gestor, etc.',
                ])
                ->update();
        }

        $this->execute(
            "UPDATE adms_request_type_stages
             SET hierarchy_level = 1
             WHERE approver_kind = 'immediate'
               AND (hierarchy_level IS NULL OR hierarchy_level < 1)"
        );
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_request_type_stages')) {
            return;
        }
        $table = $this->table('adms_request_type_stages');
        if ($table->hasColumn('hierarchy_level')) {
            $table->removeColumn('hierarchy_level')->update();
        }
    }
}
