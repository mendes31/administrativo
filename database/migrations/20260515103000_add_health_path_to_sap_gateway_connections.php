<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Caminho de health na API gateway + rótulos de páginas (administrativo → API → Service Layer).
 */
final class AddHealthPathToSapGatewayConnections extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sap_service_layer_connections')) {
            $table = $this->table('adms_sap_service_layer_connections');
            if (!$table->hasColumn('health_path')) {
                $table
                    ->addColumn('health_path', 'string', [
                        'limit' => 191,
                        'null' => false,
                        'default' => '/health',
                        'after' => 'base_url',
                        'comment' => 'Caminho na API gateway para teste GET (ex.: /health, /api/status)',
                    ])
                    ->update();
            }
        }

        if ($this->hasTable('adms_pages')) {
            $map = [
                'SapServiceLayerConnections' => 'API SAP (integração)',
                'SaveSapServiceLayerConnection' => 'Salvar conexão API SAP',
                'DeleteSapServiceLayerConnection' => 'Eliminar conexão API SAP',
                'TestSapServiceLayerConnection' => 'Testar conexão API SAP',
            ];
            foreach ($map as $controller => $name) {
                $esc = str_replace("'", "''", $controller);
                $n = str_replace("'", "''", $name);
                $this->execute("UPDATE adms_pages SET name = '{$n}', updated_at = NOW() WHERE controller = '{$esc}' LIMIT 1");
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sap_service_layer_connections')) {
            $table = $this->table('adms_sap_service_layer_connections');
            if ($table->hasColumn('health_path')) {
                $table->removeColumn('health_path')->update();
            }
        }

        if ($this->hasTable('adms_pages')) {
            $map = [
                'SapServiceLayerConnections' => 'Service Layer SAP',
                'SaveSapServiceLayerConnection' => 'Salvar conexão Service Layer SAP',
                'DeleteSapServiceLayerConnection' => 'Eliminar conexão Service Layer SAP',
                'TestSapServiceLayerConnection' => 'Testar conexão Service Layer SAP',
            ];
            foreach ($map as $controller => $name) {
                $esc = str_replace("'", "''", $controller);
                $n = str_replace("'", "''", $name);
                $this->execute("UPDATE adms_pages SET name = '{$n}', updated_at = NOW() WHERE controller = '{$esc}' LIMIT 1");
            }
        }
    }
}
