<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Mapeia tabelas MySQL para o módulo funcional do sistema (menu).
 */
class DatabaseSchemaModuleResolver
{
    /** @var array<string, string> */
    private const EXACT = [
        'adms_customer' => 'Parceiros de Negócio — Clientes',
        'adms_supplier' => 'Parceiros de Negócio — Fornecedores',
        'adms_meeting_rooms' => 'Reserva de Salas',
        'adms_branches' => 'Administração / Sistema',
        'calendar_holidays' => 'Administração / Sistema',
        'calendar_settings' => 'Administração / Sistema',
    ];

    /**
     * Padrões regex (ordem importa — primeiro match vence).
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        '/^adms_sst_/' => 'Segurança e Medicina (SST)',
        '/^adms_booking_/' => 'Reserva de Salas',
        '/^adms_room_/' => 'Reserva de Salas',
        '/^adms_(positions|cost_center|departments|work_shifts|collaborator)$/' => 'Cadastro',
        '/^adms_access_levels/' => 'Cadastro',
        '/^adms_users($|_)/' => 'Cadastro',
        '/^adms_(accounts_plan|bank_|frequency|payment_method|partial_value|movements)/' => 'Financeiro',
        '/^adms_(pay|receive)$/' => 'Financeiro',
        '/^adms_(informativos|timeline_|company_event|gamification_)/' => 'Comunicação Interna',
        '/^adms_(trainings|training_)/' => 'Gestão de Treinamentos',
        '/^adms_evaluation_/' => 'Gestão de Treinamentos',
        '/^adms_(policies|employee_|employment_history|payroll_|pdi_|performance_|competenc|people_|request_types)/' => 'Gestão de Pessoas',
        '/^adms_documents?$/' => 'Garantia da Qualidade',
        '/^adms_document_/' => 'Garantia da Qualidade',
        '/^adms_strategic_/' => 'Planejamento Estratégico',
        '/^adms_action_plans$/' => 'Planejamento Estratégico',
        '/^adms_(dynamic_report|dashboard|spreadsheet|report_|kpi_)/' => 'Relatórios',
        '/^adms_(pages|groups_pages|packages_pages|log_|login_|logs$|sessions$|invalidated_sessions|email_config|notification_settings|whatsapp_config|push_|sap_api_config|mcp_api_config|password_|slow_request|notifications$)/' => 'Administração / Sistema',
        '/^adms_user_calendar_entries$/' => 'Reserva de Salas',
        '/^crm_/' => 'CRM',
        '/^inv_/' => 'Estoque',
        '/^lgpd_/' => 'LGPD',
        '/^proj_/' => 'Gestão de Projetos',
        '/^rh_/' => 'RH / Recrutamento',
        '/^sac_/' => 'SAC',
        '/^pe_/' => 'Planejamento Estratégico',
        '/^phinx/' => 'Migrações (Phinx)',
        '/^room(s)?_/' => 'Reserva de Salas',
        '/^booking_/' => 'Reserva de Salas',
        '/^sst_/' => 'Segurança e Medicina (SST)',
    ];

    /** Fallback para tabelas adms_* não mapeadas acima. */
    private const ADMS_FALLBACK = 'Administração / Sistema';

    public function resolve(string $tableName): string
    {
        $lower = strtolower(trim($tableName));
        if ($lower === '') {
            return 'Geral';
        }

        if (isset(self::EXACT[$lower])) {
            return self::EXACT[$lower];
        }

        foreach (self::PATTERNS as $pattern => $module) {
            if (preg_match($pattern, $lower) === 1) {
                return $module;
            }
        }

        if (str_starts_with($lower, 'adms_')) {
            return self::ADMS_FALLBACK;
        }

        return 'Geral';
    }
}
