<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Restaura a etapa Banco de Talentos no catálogo do pipeline ATS.
 * Reaproveita o código legado `banco_talentos` (status_processo / filtros / LGPD).
 */
final class AddRhPipelineStageBancoTalentos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_pipeline_stages')) {
            return;
        }

        $existing = $this->fetchRow(
            "SELECT id FROM rh_pipeline_stages WHERE code = 'banco_talentos' LIMIT 1"
        );
        if ($existing) {
            return;
        }

        $this->table('rh_pipeline_stages')->insert([
            'code' => 'banco_talentos',
            'label' => 'Banco de Talentos',
            'display_order' => 35,
            'column_class' => 'bg-primary-subtle',
            'is_active' => 1,
        ])->saveData();
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_pipeline_stages')) {
            return;
        }

        $this->execute("DELETE FROM rh_pipeline_stages WHERE code = 'banco_talentos' LIMIT 1");
    }
}
