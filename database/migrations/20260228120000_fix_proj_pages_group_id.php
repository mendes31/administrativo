<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Ajusta o grupo das páginas do módulo Gestão de Projetos.
 *
 * Em alguns ambientes o ID 38 já estava sendo usado para outro grupo
 * (ex.: Dashboards KPI). Nesta migration buscamos o ID real do grupo
 * "Gestão de Projetos" por NOME e atualizamos as páginas de projeto
 * para apontarem para esse ID.
 */
final class FixProjPagesGroupId extends AbstractMigration
{
    public function up(): void
    {
        // Descobrir o ID real do grupo "Gestão de Projetos"
        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Projetos' LIMIT 1");
        if (!$group || empty($group['id'])) {
            // Grupo ainda não existe, nada a fazer
            return;
        }

        $groupId = (int)$group['id'];

        // Lista de controllers do módulo de Gestão de Projetos
        $controllers = [
            'ListProjects',
            'CreateProject',
            'UpdateProject',
            'DeleteProject',
            'ListProjectStages',
            'CreateProjectStage',
            'UpdateProjectStage',
            'DeleteProjectStage',
            'ListStageGroups',
            'CreateStageGroup',
            'UpdateStageGroup',
            'DeleteStageGroup',
            'ProjectPartnerLookup',
        ];

        $in = implode("','", array_map('addslashes', $controllers));

        $this->execute(
            "UPDATE adms_pages 
             SET adms_groups_page_id = {$groupId}
             WHERE controller IN ('{$in}')"
        );
    }

    public function down(): void
    {
        // Reversão não é crítica aqui; não alteramos nada além do grupo.
        // Deixamos o método como no-op para evitar sobrescritas indevidas.
    }
}

