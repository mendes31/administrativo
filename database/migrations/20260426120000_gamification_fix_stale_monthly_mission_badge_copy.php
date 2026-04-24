<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Corrige instalações que ainda têm textos/meta de missões semanais ou descrições de badges com "semana",
 * quando a migração mensal não coincidiu com os títulos exactos guardados na BD.
 */
final class GamificationFixStaleMonthlyMissionBadgeCopy extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_gamification_badges')) {
            $this->execute(
                'UPDATE adms_gamification_badges
                 SET criteria_key = \'monthly_missions_completed\',
                     description = REPLACE(REPLACE(REPLACE(description, \'semanais\', \'mensais\'), \'semanal\', \'mensal\'), \'Semanal\', \'Mensal\')
                 WHERE criteria_key = \'weekly_missions_completed\''
            );
            $this->execute(
                'UPDATE adms_gamification_badges
                 SET description = \'Completou ao menos 4 missões mensais.\'
                 WHERE slug = \'presenca-constante\'
                   AND (description LIKE \'%semana%\' OR description LIKE \'%Semanal%\')'
            );
        }

        if (!$this->hasTable('adms_gamification_weekly_missions')) {
            return;
        }

        $this->execute(
            'UPDATE adms_gamification_weekly_missions
             SET title = \'Fazer 12 comentários relevantes (mês)\',
                 description = \'Comente de forma construtiva 12 vezes no mês civil.\',
                 target_value = 12
             WHERE event_key = \'timeline_comment_created\'
               AND (description LIKE \'%semana%\' OR description LIKE \'%Semana%\' OR target_value = 3)'
        );
        $this->execute(
            'UPDATE adms_gamification_weekly_missions
             SET title = \'Criar 4 publicações úteis (mês)\',
                 description = \'Publique ao menos quatro conteúdos relevantes no mês.\',
                 target_value = 4
             WHERE event_key = \'timeline_post_created\'
               AND (description LIKE \'%semana%\' OR description LIKE \'%Semana%\' OR target_value = 1)'
        );
        $this->execute(
            'UPDATE adms_gamification_weekly_missions
             SET title = \'Participar de 8 enquetes (mês)\',
                 description = \'Vote em oito enquetes durante o mês.\',
                 target_value = 8
             WHERE event_key = \'timeline_poll_vote\'
               AND (description LIKE \'%semana%\' OR description LIKE \'%Semana%\' OR target_value = 2)'
        );
        $this->execute(
            'UPDATE adms_gamification_weekly_missions
             SET title = \'Interagir em 20 ações válidas (mês)\',
                 description = \'Some 20 reações qualificadas no mês.\',
                 target_value = 20
             WHERE event_key = \'timeline_reaction_created\'
               AND (description LIKE \'%semana%\' OR description LIKE \'%Semana%\' OR target_value = 5)'
        );
    }

    public function down(): void
    {
    }
}
