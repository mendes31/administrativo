<?php

use Phinx\Seed\AbstractSeed;

class AddCrmPipelineStages extends AbstractSeed
{
    /**
     * Seed para criar as etapas padrão do pipeline CRM
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Prospecção',
                'description' => 'Primeiro contato com o lead, identificação de interesse',
                'display_order' => 1,
                'color' => '#0d6efd',
                'conversion_probability' => 20,
                'is_active' => 1,
                'is_final_stage' => 0,
                'stage_type' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Qualificação',
                'description' => 'Lead qualificado, verificação de fit e necessidades',
                'display_order' => 2,
                'color' => '#6f42c1',
                'conversion_probability' => 40,
                'is_active' => 1,
                'is_final_stage' => 0,
                'stage_type' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Proposta',
                'description' => 'Proposta comercial enviada ao cliente',
                'display_order' => 3,
                'color' => '#fd7e14',
                'conversion_probability' => 60,
                'is_active' => 1,
                'is_final_stage' => 0,
                'stage_type' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Negociação',
                'description' => 'Negociação de valores, prazos e condições',
                'display_order' => 4,
                'color' => '#d63384',
                'conversion_probability' => 80,
                'is_active' => 1,
                'is_final_stage' => 0,
                'stage_type' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Fechamento',
                'description' => 'Aguardando aprovação final e assinatura',
                'display_order' => 5,
                'color' => '#198754',
                'conversion_probability' => 95,
                'is_active' => 1,
                'is_final_stage' => 0,
                'stage_type' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Ganho',
                'description' => 'Negócio fechado com sucesso',
                'display_order' => 6,
                'color' => '#157347',
                'conversion_probability' => 100,
                'is_active' => 1,
                'is_final_stage' => 1,
                'stage_type' => 'won',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Perdido',
                'description' => 'Negócio perdido',
                'display_order' => 7,
                'color' => '#dc3545',
                'conversion_probability' => 0,
                'is_active' => 1,
                'is_final_stage' => 1,
                'stage_type' => 'lost',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $table = $this->table('crm_pipeline_stages');
        $table->insert($data)->saveData();
    }
}

