<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Parametrização local dos multiplicadores HVAC (critério 8).
 * SAP grava apenas CM / PROB / OTHER; multiplicadores calibram aqui.
 */
final class CreateInvEnergyClassFactors extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_energy_class_factors')) {
            $this->table('inv_energy_class_factors')
                ->addColumn('code', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('label', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('multiplier', 'decimal', ['precision' => 8, 'scale' => 4, 'default' => '1.0000'])
                ->addColumn('notes', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addColumn('sort_order', 'integer', ['default' => 0])
                ->addColumn('is_active', 'boolean', ['default' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['code'], ['unique' => true, 'name' => 'uniq_inv_energy_class_code'])
                ->create();
        }

        $now = date('Y-m-d H:i:s');
        $seed = [
            ['code' => 'NA', 'label' => 'Não aplicável', 'multiplier' => '0.0000', 'sort_order' => 5, 'notes' => 'Padrão SAP (UDF ClasseHvac = NA). Não entra no rateio crit. 8.'],
            ['code' => 'OTHER', 'label' => 'Outros (legado planilha)', 'multiplier' => '1.0000', 'sort_order' => 10, 'notes' => 'Equivalente OUTRO na planilha Tiaraju; SAP não usa este código.'],
            ['code' => 'CM', 'label' => 'Cápsula mole', 'multiplier' => '1.2000', 'sort_order' => 20, 'notes' => 'Fator correção secagem 1,2 (linha 1232).'],
            ['code' => 'PROB', 'label' => 'Probiótico', 'multiplier' => '1.2000', 'sort_order' => 30, 'notes' => 'Fator correção secagem 1,2 (linha 1232).'],
        ];

        foreach ($seed as $row) {
            $exists = $this->fetchRow(
                'SELECT id FROM inv_energy_class_factors WHERE code = ' . $this->getAdapter()->getConnection()->quote($row['code'])
            );
            if ($exists) {
                continue;
            }

            $this->table('inv_energy_class_factors')->insert(array_merge($row, [
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]))->saveData();
        }

        $this->registerPages();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN ('ListInvEnergyClassFactors', 'SaveInvEnergyClassFactors')");
        }

        if ($this->hasTable('inv_energy_class_factors')) {
            $this->table('inv_energy_class_factors')->drop()->save();
        }
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow('SELECT id FROM adms_groups_pages WHERE id = 33 LIMIT 1');
        $groupId = $group ? (int)$group['id'] : 33;
        $now = date('Y-m-d H:i:s');

        $pages = [
            [
                'name' => 'Classes HVAC (critério 8)',
                'controller' => 'ListInvEnergyClassFactors',
                'controller_url' => 'list-inventory-energy-class-factors',
                'directory' => 'inventory',
                'obs' => 'Parametrização dos multiplicadores CM/PROB/OTHER para rateio CFIX crit. 8.',
            ],
            [
                'name' => 'Salvar Classes HVAC',
                'controller' => 'SaveInvEnergyClassFactors',
                'controller_url' => 'save-inventory-energy-class-factors',
                'directory' => 'inventory',
                'obs' => 'Persistência dos multiplicadores HVAC.',
            ],
        ];

        foreach ($pages as $page) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $this->getAdapter()->getConnection()->quote($page['controller'])
            );
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $page['name'],
                'controller' => $page['controller'],
                'controller_url' => $page['controller_url'],
                'directory' => $page['directory'],
                'obs' => $page['obs'],
                'adms_groups_page_id' => $groupId,
                'public_page' => 0,
                'page_status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->saveData();
        }
    }
}
