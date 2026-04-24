<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Congela meta/recompensa/título por mês em cada linha de progresso.
 */
final class MissionProgressMonthSnapshots extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_gamification_user_mission_progress')) {
            return;
        }
        $exists = $this->fetchRow(
            "SHOW COLUMNS FROM adms_gamification_user_mission_progress LIKE 'snapshot_target_value'"
        );
        if (empty($exists)) {
            $this->execute(
                'ALTER TABLE adms_gamification_user_mission_progress
                 ADD COLUMN snapshot_target_value INT UNSIGNED NULL DEFAULT NULL AFTER week_start_date,
                 ADD COLUMN snapshot_reward_points INT UNSIGNED NULL DEFAULT NULL AFTER snapshot_target_value,
                 ADD COLUMN snapshot_title VARCHAR(160) NULL DEFAULT NULL AFTER snapshot_reward_points,
                 ADD COLUMN snapshot_description VARCHAR(255) NULL DEFAULT NULL AFTER snapshot_title'
            );
        }

        if ($this->hasTable('adms_gamification_weekly_missions')) {
            $this->execute(
                'UPDATE adms_gamification_user_mission_progress p
                 INNER JOIN adms_gamification_weekly_missions m ON m.id = p.mission_id
                 SET p.snapshot_target_value = m.target_value,
                     p.snapshot_reward_points = m.reward_points,
                     p.snapshot_title = m.title,
                     p.snapshot_description = m.description
                 WHERE p.snapshot_target_value IS NULL'
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_gamification_user_mission_progress')) {
            return;
        }
        $exists = $this->fetchRow(
            "SHOW COLUMNS FROM adms_gamification_user_mission_progress LIKE 'snapshot_target_value'"
        );
        if (!empty($exists)) {
            $this->execute(
                'ALTER TABLE adms_gamification_user_mission_progress
                 DROP COLUMN snapshot_description,
                 DROP COLUMN snapshot_title,
                 DROP COLUMN snapshot_reward_points,
                 DROP COLUMN snapshot_target_value'
            );
        }
    }
}
