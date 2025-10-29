<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seed para dados de exemplo do CRM
 * 
 * Popula o CRM com parceiros e oportunidades de teste
 */
class AddCrmSampleData extends AbstractSeed
{
    public function run(): void
    {
        // Verificar se já existem dados
        $existingPartners = $this->query('SELECT COUNT(*) as total FROM crm_partners')->fetch();
        
        if ($existingPartners['total'] > 0) {
            echo "⚠️ Já existem parceiros cadastrados. Seed não será executado.\n";
            return;
        }

        // ===== PARCEIROS DE EXEMPLO =====
        $partners = [
            [
                'code' => 'P00001',
                'name' => 'Farmácia Central',
                'trading_name' => 'FC Medicamentos',
                'type_person' => 'PJ',
                'document' => '12.345.678/0001-90',
                'email' => 'contato@farmaciacentral.com.br',
                'phone' => '(51) 3333-4444',
                'mobile' => '(51) 99999-8888',
                'city' => 'Porto Alegre',
                'state' => 'RS',
                'segment' => 'Farma',
                'partner_type' => 'Cliente',
                'priority' => 'Alta',
                'status' => 'Ativo',
                'responsible_user_id' => 1,
                'estimated_revenue' => 50000.00,
                'first_contact_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-30 days'))
            ],
            [
                'code' => 'P00002',
                'name' => 'Drogaria São Paulo',
                'trading_name' => 'DSP',
                'type_person' => 'PJ',
                'document' => '98.765.432/0001-10',
                'email' => 'comercial@drogariaSP.com.br',
                'phone' => '(11) 4444-5555',
                'mobile' => '(11) 98888-7777',
                'city' => 'São Paulo',
                'state' => 'SP',
                'segment' => 'Farma',
                'partner_type' => 'Prospect',
                'priority' => 'Média',
                'status' => 'Ativo',
                'responsible_user_id' => 1,
                'estimated_revenue' => 75000.00,
                'first_contact_date' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-15 days'))
            ],
            [
                'code' => 'P00003',
                'name' => 'Suplementos Fitness Pro',
                'trading_name' => 'Fitness Pro',
                'type_person' => 'PJ',
                'document' => '11.222.333/0001-44',
                'email' => 'vendas@fitnesspro.com.br',
                'phone' => '(21) 2222-3333',
                'mobile' => '(21) 97777-6666',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'segment' => 'Suplementos',
                'partner_type' => 'Lead',
                'priority' => 'Alta',
                'status' => 'Ativo',
                'responsible_user_id' => 1,
                'estimated_revenue' => 100000.00,
                'first_contact_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'code' => 'P00004',
                'name' => 'Rede Farma Saúde',
                'trading_name' => 'RFS',
                'type_person' => 'PJ',
                'document' => '55.666.777/0001-88',
                'email' => 'rfs@farmasaude.com.br',
                'phone' => '(41) 3456-7890',
                'mobile' => '(41) 98765-4321',
                'city' => 'Curitiba',
                'state' => 'PR',
                'segment' => 'Ambos',
                'partner_type' => 'Cliente',
                'priority' => 'Urgente',
                'status' => 'Ativo',
                'responsible_user_id' => 1,
                'estimated_revenue' => 150000.00,
                'first_contact_date' => date('Y-m-d H:i:s', strtotime('-60 days')),
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-60 days'))
            ]
        ];

        $partnersTable = $this->table('crm_partners');
        $partnersTable->insert($partners)->save();

        echo "✓ 4 parceiros de exemplo criados\n";

        // ===== OPORTUNIDADES DE EXEMPLO =====
        $opportunities = [
            [
                'code' => 'OPP00001',
                'title' => 'Venda Sistema de Gestão Completo',
                'description' => 'Implantação do sistema completo de gestão farmacêutica',
                'partner_id' => 1,
                'responsible_user_id' => 1,
                'stage_id' => 3, // Proposta
                'value' => 45000.00,
                'probability' => 60,
                'expected_close_date' => date('Y-m-d', strtotime('+30 days')),
                'next_action' => 'Apresentar proposta comercial',
                'next_action_date' => date('Y-m-d', strtotime('+7 days')),
                'source' => 'Indicação',
                'status' => 'Aberta',
                'created_by' => 1,
                'stage_entered_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'code' => 'OPP00002',
                'title' => 'Consultoria em Gestão de Estoque',
                'description' => 'Consultoria especializada para otimização de estoque',
                'partner_id' => 2,
                'responsible_user_id' => 1,
                'stage_id' => 2, // Qualificação
                'value' => 15000.00,
                'probability' => 40,
                'expected_close_date' => date('Y-m-d', strtotime('+45 days')),
                'next_action' => 'Agendar reunião de levantamento',
                'next_action_date' => date('Y-m-d', strtotime('+3 days')),
                'source' => 'Site',
                'status' => 'Aberta',
                'created_by' => 1,
                'stage_entered_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-8 days'))
            ],
            [
                'code' => 'OPP00003',
                'title' => 'Fornecimento de Suplementos Premium',
                'description' => 'Fornecimento mensal de linha premium de suplementos',
                'partner_id' => 3,
                'responsible_user_id' => 1,
                'stage_id' => 1, // Prospecção
                'value' => 80000.00,
                'probability' => 20,
                'expected_close_date' => date('Y-m-d', strtotime('+60 days')),
                'next_action' => 'Primeiro contato telefônico',
                'next_action_date' => date('Y-m-d', strtotime('+1 day')),
                'source' => 'Telefone',
                'status' => 'Aberta',
                'created_by' => 1,
                'stage_entered_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'code' => 'OPP00004',
                'title' => 'Expansão para 5 Novas Filiais',
                'description' => 'Expansão do sistema atual para cobrir 5 novas filiais',
                'partner_id' => 4,
                'responsible_user_id' => 1,
                'stage_id' => 4, // Negociação
                'value' => 120000.00,
                'probability' => 80,
                'expected_close_date' => date('Y-m-d', strtotime('+15 days')),
                'next_action' => 'Revisar contrato e condições de pagamento',
                'next_action_date' => date('Y-m-d', strtotime('+2 days')),
                'source' => 'Cliente Atual',
                'status' => 'Aberta',
                'created_by' => 1,
                'stage_entered_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-40 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
            ]
        ];

        $opportunitiesTable = $this->table('crm_opportunities');
        $opportunitiesTable->insert($opportunities)->save();

        echo "✓ 4 oportunidades de exemplo criadas\n";
        echo "\n";
        echo "═══════════════════════════════════════\n";
        echo "✅ CRM POPULADO COM DADOS DE EXEMPLO!\n";
        echo "═══════════════════════════════════════\n";
        echo "📊 Acesse: crm-kanban-pipeline\n";
        echo "👥 Acesse: crm-list-partners\n";
        echo "═══════════════════════════════════════\n";
    }
}

