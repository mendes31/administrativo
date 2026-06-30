<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Parametrização local dos fatores de complexidade (critérios 4 e 6).
 * SAP grava NA / BAIXA / MEDIA / ALTA; multiplicadores calibram aqui.
 */
final class CreateInvComplexityLevelFactors extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_complexity_level_factors')) {
            $this->table('inv_complexity_level_factors')
                ->addColumn('code', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('label', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('multiplier', 'decimal', ['precision' => 8, 'scale' => 4, 'default' => '1.0000'])
                ->addColumn('notes', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
                ->addColumn('sort_order', 'integer', ['default' => 0])
                ->addColumn('is_active', 'boolean', ['default' => true])
                ->addColumn('created_at', 'timestamp', ['null' => true])
                ->addColumn('updated_at', 'timestamp', ['null' => true])
                ->addIndex(['code'], ['unique' => true, 'name' => 'uniq_inv_complexity_level_code'])
                ->create();
        }

        $now = date('Y-m-d H:i:s');
        $seed = [
            ['code' => 'NA', 'label' => 'Não aplicável', 'multiplier' => '0.0000', 'sort_order' => 5, 'notes' => 'Padrão SAP (UDF Complexidade = N). Não entra nos critérios 4 e 6.'],
            ['code' => 'BAIXA', 'label' => 'Baixa', 'multiplier' => '2.0000', 'sort_order' => 20, 'notes' => 'SAP = B. Fator planilha Tiaraju (crit. 4).'],
            ['code' => 'MEDIA', 'label' => 'Média', 'multiplier' => '5.0000', 'sort_order' => 30, 'notes' => 'SAP = M. Fator planilha Tiaraju (crit. 4).'],
            ['code' => 'ALTA', 'label' => 'Alta', 'multiplier' => '8.0000', 'sort_order' => 40, 'notes' => 'SAP = A (se cadastrado). Fator planilha Tiaraju (crit. 4).'],
        ];

        foreach ($seed as $row) {
            $exists = $this->fetchRow(
                'SELECT id FROM inv_complexity_level_factors WHERE code = ' . $this->getAdapter()->getConnection()->quote($row['code'])
            );
            if ($exists) {
                continue;
            }

            $this->table('inv_complexity_level_factors')->insert(array_merge($row, [
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]))->saveData();
        }

        $this->migrateLegacyComplexityCodes();
        $this->registerPages();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller IN ('ListInvComplexityLevelFactors', 'SaveInvComplexityLevelFactors')");
        }

        if ($this->hasTable('inv_complexity_level_factors')) {
            $this->table('inv_complexity_level_factors')->drop()->save();
        }
    }

    private function migrateLegacyComplexityCodes(): void
    {
        if ($this->hasTable('inv_items')) {
            $this->execute("UPDATE inv_items SET complexity_level = 'BAIXA' WHERE LOWER(TRIM(complexity_level)) = 'baixa'");
            $this->execute("UPDATE inv_items SET complexity_level = 'MEDIA' WHERE LOWER(TRIM(complexity_level)) IN ('media', 'média')");
            $this->execute("UPDATE inv_items SET complexity_level = 'ALTA' WHERE LOWER(TRIM(complexity_level)) = 'alta'");
            $this->execute("UPDATE inv_items SET complexity_level = 'NA' WHERE TRIM(complexity_level) = '' OR complexity_level IS NULL");
        }

        if ($this->hasTable('inv_cost_period_items')) {
            $this->execute("UPDATE inv_cost_period_items SET complexity_level = 'BAIXA' WHERE LOWER(TRIM(complexity_level)) = 'baixa'");
            $this->execute("UPDATE inv_cost_period_items SET complexity_level = 'MEDIA' WHERE LOWER(TRIM(complexity_level)) IN ('media', 'média')");
            $this->execute("UPDATE inv_cost_period_items SET complexity_level = 'ALTA' WHERE LOWER(TRIM(complexity_level)) = 'alta'");
        }

        if ($this->hasTable('inv_cost_period_sku_snapshots')) {
            $this->execute("UPDATE inv_cost_period_sku_snapshots SET complexity_level = 'BAIXA' WHERE LOWER(TRIM(complexity_level)) = 'baixa'");
            $this->execute("UPDATE inv_cost_period_sku_snapshots SET complexity_level = 'MEDIA' WHERE LOWER(TRIM(complexity_level)) IN ('media', 'média')");
            $this->execute("UPDATE inv_cost_period_sku_snapshots SET complexity_level = 'ALTA' WHERE LOWER(TRIM(complexity_level)) = 'alta'");
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
                'name' => 'Complexidade (crit. 4/6)',
                'controller' => 'ListInvComplexityLevelFactors',
                'controller_url' => 'list-inventory-complexity-level-factors',
                'directory' => 'inventory',
                'obs' => 'Parametrização dos fatores NA/BAIXA/MEDIA/ALTA para rateio CFIX crit. 4 e 6.',
            ],
            [
                'name' => 'Salvar Complexidade',
                'controller' => 'SaveInvComplexityLevelFactors',
                'controller_url' => 'save-inventory-complexity-level-factors',
                'directory' => 'inventory',
                'obs' => 'Persistência dos fatores de complexidade.',
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
