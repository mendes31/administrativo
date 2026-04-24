<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsGamificationProgramTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('adms_gamification_levels', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('min_points', 'integer', ['signed' => false, 'default' => 0, 'null' => false])
            ->addColumn('badge_color', 'string', ['limit' => 20, 'null' => true, 'default' => null])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['min_points'], ['name' => 'idx_gamification_levels_min_points'])
            ->addIndex(['is_active', 'sort_order'], ['name' => 'idx_gamification_levels_active_order'])
            ->create();

        $this->table('adms_gamification_badges', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 140, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('criteria_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('criteria_value_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('icon', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uk_gamification_badges_slug'])
            ->addIndex(['is_active'], ['name' => 'idx_gamification_badges_active'])
            ->create();

        $this->table('adms_gamification_user_badges', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('badge_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('meta_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('awarded_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['user_id', 'badge_id'], ['unique' => true, 'name' => 'uk_gamification_user_badges'])
            ->addIndex(['user_id', 'awarded_at'], ['name' => 'idx_gamification_user_badges_user_date'])
            ->create();

        $this->table('adms_gamification_weekly_missions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('event_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('target_value', 'integer', ['signed' => false, 'default' => 1, 'null' => false])
            ->addColumn('reward_points', 'integer', ['signed' => false, 'default' => 0, 'null' => false])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('is_active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['event_key', 'is_active'], ['name' => 'idx_gamification_missions_event_active'])
            ->create();

        $this->table('adms_gamification_user_mission_progress', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('mission_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('week_start_date', 'date', ['null' => false])
            ->addColumn('current_value', 'integer', ['signed' => false, 'default' => 0, 'null' => false])
            ->addColumn('is_completed', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('completed_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['mission_id', 'user_id', 'week_start_date'], ['unique' => true, 'name' => 'uk_gamification_mission_progress_week'])
            ->addIndex(['user_id', 'week_start_date'], ['name' => 'idx_gamification_mission_progress_user_week'])
            ->create();

        $this->table('adms_gamification_anti_fraud_events', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('event_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('ref_type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('ref_id', 'integer', ['signed' => false, 'default' => 0, 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('details_json', 'text', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['user_id', 'event_key', 'created_at'], ['name' => 'idx_gamification_antifraud_user_event_date'])
            ->create();
    }
}
