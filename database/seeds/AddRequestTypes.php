<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seed para tipos de solicitações adicionais
 */
class AddRequestTypes extends AbstractSeed
{
    public function run(): void
    {
        // Forçar charset/collation
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $this->execute("SET collation_connection = 'utf8mb4_unicode_ci'");
        $this->execute("SET character_set_client = 'utf8mb4'");
        $this->execute("SET character_set_results = 'utf8mb4'");
        $this->execute("SET character_set_connection = 'utf8mb4'");

        $requestTypes = [
            [
                'code' => 'lanche',
                'name' => 'Lanche/Refeição',
                'description' => 'Solicitação de lanches, café da manhã, almoço, jantar ou coffee break para reuniões',
                'requires_responsible' => true,
                'default_responsible_user_id' => null,
                'requires_quantity' => true,
                'is_active' => true,
            ],
            [
                'code' => 'equipamento_extra',
                'name' => 'Equipamento Extra',
                'description' => 'Solicitação de equipamentos adicionais não disponíveis na sala (notebooks, tablets, etc.)',
                'requires_responsible' => true,
                'default_responsible_user_id' => null,
                'requires_quantity' => true,
                'is_active' => true,
            ],
            [
                'code' => 'limpeza',
                'name' => 'Limpeza Especial',
                'description' => 'Solicitação de limpeza especial ou preparação da sala antes da reunião',
                'requires_responsible' => true,
                'default_responsible_user_id' => null,
                'requires_quantity' => false,
                'is_active' => true,
            ],
            [
                'code' => 'outros',
                'name' => 'Outros',
                'description' => 'Outras solicitações não categorizadas',
                'requires_responsible' => true,
                'default_responsible_user_id' => null,
                'requires_quantity' => false,
                'is_active' => true,
            ],
        ];

        foreach ($requestTypes as $type) {
            $existing = $this->query(
                "SELECT id FROM adms_request_types WHERE code = :code",
                ['code' => $type['code']]
            )->fetch();

            if (!$existing) {
                $this->table('adms_request_types')->insert([
                    'code' => $type['code'],
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'requires_responsible' => $type['requires_responsible'] ? 1 : 0,
                    'default_responsible_user_id' => $type['default_responsible_user_id'],
                    'requires_quantity' => $type['requires_quantity'] ? 1 : 0,
                    'is_active' => $type['is_active'] ? 1 : 0,
                    'status' => $type['is_active'] ? 1 : 0, // Manter compatibilidade com campo existente
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])->save();
            }
        }
    }
}
