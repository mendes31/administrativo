<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Limite de escalação por etapa (quantos níveis sobe na hierarquia antes do RH).
 */
final class AddMaxEscalationLevelsToRequestWorkflow extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_request_type_stages')) {
            $table = $this->table('adms_request_type_stages');
            if (!$table->hasColumn('max_escalation_levels')) {
                $table->addColumn('max_escalation_levels', 'integer', [
                    'signed' => false,
                    'default' => 1,
                    'null' => false,
                    'after' => 'escalate_policy',
                    'comment' => 'Quantos níveis acima na hierarquia antes de ir ao RH (0=vai ao RH no SLA)',
                ])->update();
            }
        }

        if ($this->hasTable('adms_employee_requests')) {
            $table = $this->table('adms_employee_requests');
            if (!$table->hasColumn('escalation_count')) {
                $table->addColumn('escalation_count', 'integer', [
                    'signed' => false,
                    'default' => 0,
                    'null' => false,
                    'after' => 'escalate_after_hours',
                ]);
            }
            if (!$table->hasColumn('max_escalation_levels')) {
                $table->addColumn('max_escalation_levels', 'integer', [
                    'signed' => false,
                    'default' => 1,
                    'null' => true,
                    'after' => 'escalation_count',
                ]);
            }
            $table->update();

            // Snapshot padrão nas abertas
            $this->execute(
                "UPDATE adms_employee_requests
                 SET max_escalation_levels = 1
                 WHERE status = 'pending_manager_approval'
                   AND (max_escalation_levels IS NULL)"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_employee_requests')) {
            $table = $this->table('adms_employee_requests');
            foreach (['max_escalation_levels', 'escalation_count'] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }

        if ($this->hasTable('adms_request_type_stages')) {
            $table = $this->table('adms_request_type_stages');
            if ($table->hasColumn('max_escalation_levels')) {
                $table->removeColumn('max_escalation_levels')->update();
            }
        }
    }
}
