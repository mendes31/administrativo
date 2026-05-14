<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Conexões nomeadas à SAP Business One Service Layer (credenciais na base, sem .env).
 */
final class CreateAdmsSapServiceLayerConnections extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_sap_service_layer_connections')) {
            return;
        }

        $this->table('adms_sap_service_layer_connections')
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('base_url', 'string', ['limit' => 512, 'null' => false])
            ->addColumn('company_db', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('username', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('password', 'text', ['null' => false, 'comment' => 'Palavra-passe do utilizador B1 na Service Layer'])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => false, 'comment' => 'Conexão usada quando o código não indica outra'])
            ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['name'], ['unique' => true, 'name' => 'uniq_sap_sl_conn_name'])
            ->create();
    }
}
