<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * RH opcional no fluxo do tipo de solicitação.
 */
final class AddRequiresHrApprovalToRequestTypes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_request_types')) {
            return;
        }

        $table = $this->table('adms_request_types');
        if (!$table->hasColumn('requires_hr_approval')) {
            $table->addColumn('requires_hr_approval', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'requires_manager_approval',
                'comment' => 'Se false, a aprovação do gestor (ou auto) já encerra sem etapa RH',
            ])->update();
        }

        // Tipos sem etapa RH no banco de stages: marca requires_hr_approval=0 quando só immediate
        if ($this->hasTable('adms_request_type_stages')) {
            $this->execute(
                "UPDATE adms_request_types t
                 SET t.requires_hr_approval = 0
                 WHERE t.requires_manager_approval = 1
                   AND EXISTS (
                     SELECT 1 FROM adms_request_type_stages s
                     WHERE s.request_type_id = t.id AND s.approver_kind = 'immediate' AND s.is_active = 1
                   )
                   AND NOT EXISTS (
                     SELECT 1 FROM adms_request_type_stages s2
                     WHERE s2.request_type_id = t.id AND s2.approver_kind = 'hr' AND s2.is_active = 1
                   )"
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_request_types')) {
            return;
        }
        $table = $this->table('adms_request_types');
        if ($table->hasColumn('requires_hr_approval')) {
            $table->removeColumn('requires_hr_approval')->update();
        }
    }
}
