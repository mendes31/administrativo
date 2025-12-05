<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seed para tipos de solicitação
 */
class AddRequestTypes extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $requestTypes = [
            [
                'code' => 'vacation',
                'name' => 'Férias',
                'description' => 'Solicitação de férias',
                'requires_manager_approval' => true,
                'requires_dates' => true,
                'requires_days' => true,
                'requires_amount' => false,
                'icon' => 'fa-calendar-alt',
                'color' => 'success',
                'status' => true,
                'sort_order' => 1
            ],
            [
                'code' => 'time_off',
                'name' => 'Afastamento',
                'description' => 'Solicitação de afastamento',
                'requires_manager_approval' => true,
                'requires_dates' => true,
                'requires_days' => true,
                'requires_amount' => false,
                'icon' => 'fa-user-clock',
                'color' => 'warning',
                'status' => true,
                'sort_order' => 2
            ],
            [
                'code' => 'document',
                'name' => 'Documento',
                'description' => 'Solicitação de documentos',
                'requires_manager_approval' => false,
                'requires_dates' => false,
                'requires_days' => false,
                'requires_amount' => false,
                'icon' => 'fa-file-alt',
                'color' => 'info',
                'status' => true,
                'sort_order' => 3
            ],
            [
                'code' => 'salary_advance',
                'name' => 'Adiantamento Salarial',
                'description' => 'Solicitação de adiantamento salarial',
                'requires_manager_approval' => false,
                'requires_dates' => false,
                'requires_days' => false,
                'requires_amount' => true,
                'icon' => 'fa-money-bill-wave',
                'color' => 'primary',
                'status' => true,
                'sort_order' => 4
            ],
            [
                'code' => 'other',
                'name' => 'Outro',
                'description' => 'Outras solicitações',
                'requires_manager_approval' => false,
                'requires_dates' => false,
                'requires_days' => false,
                'requires_amount' => false,
                'icon' => 'fa-ellipsis-h',
                'color' => 'secondary',
                'status' => true,
                'sort_order' => 5
            ],
        ];

        $table = $this->table('adms_request_types');

        foreach ($requestTypes as $type) {
            // Verificar se já existe
            $code = $type['code'];
            $exists = $this->fetchRow(
                "SELECT id FROM adms_request_types WHERE code = '{$code}'"
            );

            if (!$exists) {
                $table->insert($type)->saveData();
                echo "✓ Tipo de solicitação '{$type['name']}' criado\n";
            } else {
                echo "⚠ Tipo de solicitação '{$type['name']}' já existe\n";
            }
        }
    }
}

