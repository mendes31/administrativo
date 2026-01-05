<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration para criar a tabela adms_sap_api_config
 * responsável por armazenar as credenciais e parâmetros
 * de integração com a API do SAP B1.
 */
final class CreateAdmsSapApiConfig extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_sap_api_config')) {
            return;
        }

        $table = $this->table('adms_sap_api_config');

        $table
            ->addColumn('base_url', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('api_token', 'text', [
                'null' => false,
            ])
            ->addColumn('timeout_ms', 'integer', [
                'null' => false,
                'default' => 30000,
                'comment' => 'Timeout em milissegundos utilizado nas requisições',
            ])
            ->addColumn('page_size', 'integer', [
                'null' => false,
                'default' => 5000,
                'comment' => 'Quantidade máxima de registros por página ao consultar a API',
            ])
            ->addColumn('health_endpoint', 'string', [
                'limit' => 191,
                'null' => false,
                'default' => '/health',
                'comment' => 'Endpoint utilizado no teste de conexão',
            ])
            ->addColumn('is_active', 'boolean', [
                'null' => false,
                'default' => true,
            ])
            ->addColumn('created_at', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->create();
    }
}






