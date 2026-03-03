<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Ajusta o grupo das páginas de Recrutamento / Currículos / Candidatos
 * para o grupo correto "Gestão de Pessoas".
 *
 * Em alguns ambientes o ID usado nos seeds (30) corresponde ao grupo
 * "Informativos". Esta migration busca o ID real do grupo
 * "Gestão de Pessoas" por NOME e aponta essas páginas para ele.
 */
final class FixRhRecruitmentGroupId extends AbstractMigration
{
    public function up(): void
    {
        // Descobrir o ID real do grupo "Gestão de Pessoas"
        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Gestão de Pessoas' LIMIT 1");
        if (!$group || empty($group['id'])) {
            // Grupo ainda não existe, nada a fazer
            return;
        }

        $groupId = (int)$group['id'];

        // Controllers do módulo Recrutamento / Currículos
        $controllers = [
            'RhCandidatos',
            'RhCandidatosCreate',
            'RhCandidatosEdit',
            'RhCandidatosView',
            'RhCandidatosDelete',
            'RhVagas',
            'RhVagasCreate',
            'RhVagasEdit',
            'RhVagasView',
            'RhVagasDelete',
            'RhVagasCandidatos',
            'RhVagasPipeline',
            'RhCandidatosVagas',
            'RhKpiDashboard',
            'RhEntrevistas',
            'RhEntrevistasCreate',
            'RhEntrevistasView',
            'RhEntrevistasEdit',
            'RhEntrevistasDelete',
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
        // Sem reversão automática para evitar apontar novamente para grupo incorreto.
    }
}

