<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regras personalizadas do fluxo: níveis que pulam o gestor imediato.
 */
final class AddSkipImmediateAccessLevelsToRequestTypes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_request_types')) {
            return;
        }

        $table = $this->table('adms_request_types');
        if (!$table->hasColumn('skip_immediate_requester_level_ids')) {
            $table->addColumn('skip_immediate_requester_level_ids', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array de adms_access_levels.id do solicitante que vão direto ao RH',
            ]);
        }
        if (!$table->hasColumn('skip_immediate_supervisor_level_ids')) {
            $table->addColumn('skip_immediate_supervisor_level_ids', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array: se o imediato tem um destes níveis (ex. Diretoria), pula para RH',
            ]);
        }
        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_request_types')) {
            return;
        }

        $table = $this->table('adms_request_types');
        foreach (['skip_immediate_requester_level_ids', 'skip_immediate_supervisor_level_ids'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
