<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Regista páginas do módulo API SAP (integração) em instalações já existentes.
 * A matriz de permissões por nível fica a cargo das seeds (ex.: {@see SyncAccessLevelsPages}, {@see SyncSapIntegrationPagesAcl}).
 */
final class RegisterSapServiceLayerConnectionPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $controllers = [
            ['name' => 'Service Layer SAP', 'controller' => 'SapServiceLayerConnections', 'controller_url' => 'sap-service-layer-connections', 'obs' => 'Conexões nomeadas à SAP B1 Service Layer (credenciais na base).'],
            ['name' => 'Salvar conexão Service Layer SAP', 'controller' => 'SaveSapServiceLayerConnection', 'controller_url' => 'save-sap-service-layer-connection', 'obs' => 'Criar ou actualizar conexão Service Layer.'],
            ['name' => 'Eliminar conexão Service Layer SAP', 'controller' => 'DeleteSapServiceLayerConnection', 'controller_url' => 'delete-sap-service-layer-connection', 'obs' => 'Remover conexão Service Layer.'],
            ['name' => 'Testar conexão Service Layer SAP', 'controller' => 'TestSapServiceLayerConnection', 'controller_url' => 'test-sap-service-layer-connection', 'obs' => 'Testar login na Service Layer.'],
        ];

        $g = $this->fetchRow("SELECT adms_groups_page_id AS gid FROM adms_pages WHERE controller = 'SapApiConfig' LIMIT 1");
        $gid = (int) ($g['gid'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($controllers as $c) {
            $ctrl = $c['controller'];
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$this->escapeSql($ctrl)}' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $c['name'],
                'controller' => $c['controller'],
                'controller_url' => $c['controller_url'],
                'directory' => 'settings',
                'obs' => $c['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $names = ['SapServiceLayerConnections', 'SaveSapServiceLayerConnection', 'DeleteSapServiceLayerConnection', 'TestSapServiceLayerConnection'];
        foreach ($names as $ctrl) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$this->escapeSql($ctrl)}' LIMIT 1");
            if (!$row || empty($row['id'])) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }
    }

    private function escapeSql(string $s): string
    {
        return str_replace("'", "''", $s);
    }
}
