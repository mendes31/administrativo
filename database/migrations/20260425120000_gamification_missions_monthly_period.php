<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Missões passam a ser mensais: week_start_date passa a guardar sempre o 1.º dia do mês (Y-m-01).
 * Agrega progressos antigos (várias semanas no mesmo mês) numa única linha por (missão, utilizador, mês).
 * Badge: critério weekly_missions_completed -> monthly_missions_completed (mantém o mesmo significado de contagem).
 */
final class GamificationMissionsMonthlyPeriod extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_gamification_user_mission_progress')) {
            return;
        }

        $this->execute('DROP TABLE IF EXISTS _gamification_mission_progress_mig');
        $this->execute(
            'CREATE TABLE _gamification_mission_progress_mig AS
             SELECT mission_id,
                    user_id,
                    STR_TO_DATE(CONCAT(DATE_FORMAT(week_start_date, \'%Y-%m\'), \'-01\'), \'%Y-%m-%d\') AS month_start,
                    MAX(current_value) AS current_value,
                    MAX(CASE WHEN is_completed THEN 1 ELSE 0 END) AS is_completed_int,
                    MIN(created_at) AS created_at,
                    MAX(updated_at) AS updated_at,
                    MAX(CASE WHEN is_completed THEN completed_at END) AS completed_at
             FROM adms_gamification_user_mission_progress
             GROUP BY mission_id, user_id, DATE_FORMAT(week_start_date, \'%Y-%m\')'
        );

        $this->execute('DELETE FROM adms_gamification_user_mission_progress');

        $this->execute(
            'INSERT INTO adms_gamification_user_mission_progress
                (mission_id, user_id, week_start_date, current_value, is_completed, created_at, updated_at, completed_at)
             SELECT mission_id, user_id, month_start, current_value,
                    CASE WHEN is_completed_int > 0 THEN 1 ELSE 0 END,
                    created_at, updated_at, completed_at
             FROM _gamification_mission_progress_mig'
        );

        $this->execute('DROP TABLE IF EXISTS _gamification_mission_progress_mig');

        if ($this->hasTable('adms_gamification_badges')) {
            $this->execute(
                'UPDATE adms_gamification_badges
                 SET criteria_key = \'monthly_missions_completed\',
                     description = REPLACE(REPLACE(description, \'semanais\', \'mensais\'), \'semanal\', \'mensal\')
                 WHERE criteria_key = \'weekly_missions_completed\''
            );
        }

        if ($this->hasTable('adms_gamification_weekly_missions')) {
            $this->execute(
                "UPDATE adms_gamification_weekly_missions SET title = 'Fazer 12 comentários relevantes (mês)',
                    description = 'Comente de forma construtiva 12 vezes no mês civil.', target_value = 12
                 WHERE title = 'Fazer 3 comentários relevantes'"
            );
            $this->execute(
                "UPDATE adms_gamification_weekly_missions SET title = 'Criar 4 publicações úteis (mês)',
                    description = 'Publique ao menos quatro conteúdos relevantes no mês.', target_value = 4
                 WHERE title = 'Criar 1 publicação útil'"
            );
            $this->execute(
                "UPDATE adms_gamification_weekly_missions SET title = 'Participar de 8 enquetes (mês)',
                    description = 'Vote em oito enquetes durante o mês.', target_value = 8
                 WHERE title = 'Participar de 2 enquetes'"
            );
            $this->execute(
                "UPDATE adms_gamification_weekly_missions SET title = 'Interagir em 20 ações válidas (mês)',
                    description = 'Some 20 reações qualificadas no mês.', target_value = 20
                 WHERE title = 'Interagir em 5 ações válidas'"
            );
        }
    }

    public function down(): void
    {
        // Reversão não restaura progresso semanal granular.
    }
}
