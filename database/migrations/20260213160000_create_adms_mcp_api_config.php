<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsMcpApiConfig extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('adms_mcp_api_config')) {
            return;
        }

        $this->table('adms_mcp_api_config')
            ->addColumn('base_url', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('is_active', 'boolean', [
                'default' => 1,
                'null' => false,
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('updated_at', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->create();
    }
}

