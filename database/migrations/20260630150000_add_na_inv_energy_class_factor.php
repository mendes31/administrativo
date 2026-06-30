<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Classe NA (Não aplicável) — padrão SAP UDF ClasseHvac; multiplicador 0 no crit. 8. */
final class AddNaInvEnergyClassFactor extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_energy_class_factors')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $exists = $this->fetchRow("SELECT id FROM inv_energy_class_factors WHERE code = 'NA' LIMIT 1");
        if (!$exists) {
            $this->table('inv_energy_class_factors')->insert([
                'code' => 'NA',
                'label' => 'Não aplicável',
                'multiplier' => '0.0000',
                'notes' => 'Padrão SAP (UDF ClasseHvac = NA). SKU não entra no rateio HVAC (crit. 8).',
                'sort_order' => 5,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->saveData();
        }

        $this->execute(
            "UPDATE inv_energy_class_factors SET label = 'Outros (legado planilha)', notes = 'Equivalente OUTRO na planilha Tiaraju; SAP não usa este código.'
             WHERE code = 'OTHER'"
        );
    }

    public function down(): void
    {
        if ($this->hasTable('inv_energy_class_factors')) {
            $this->execute("DELETE FROM inv_energy_class_factors WHERE code = 'NA'");
        }
    }
}
