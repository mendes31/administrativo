<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Gamificação: regras configuráveis (timeline), ledger de pontos e quizzes independentes de avaliações.
 */
final class CreateAdmsGamificationTables extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_gamification_timeline_rules')) {
            return;
        }

        $this->table('adms_gamification_timeline_rules', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('event_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('points', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('max_awards_per_user_per_day', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('max_awards_per_user_total', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['event_key'], ['unique' => true])
            ->create();

        $this->table('adms_gamification_point_ledger', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('source_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('event_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('ref_type', 'string', ['limit' => 64, 'null' => false, 'default' => ''])
            ->addColumn('ref_id', 'biginteger', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('points', 'integer', ['null' => false])
            ->addColumn('meta_json', 'text', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'])
            ->addIndex(['created_at'])
            ->addIndex(['user_id', 'event_key', 'ref_type', 'ref_id'], ['unique' => true, 'name' => 'uk_gamification_ledger_event'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_gamification_quizzes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('summary', 'text', ['null' => true])
            ->addColumn('status', 'enum', ['values' => ['draft', 'published', 'archived'], 'default' => 'draft'])
            ->addColumn('passing_percent', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('max_attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
            ->addColumn('points_on_completion', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('available_from', 'datetime', ['null' => true])
            ->addColumn('available_until', 'datetime', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['slug'], ['unique' => true])
            ->addIndex(['status'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_gamification_quiz_questions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('quiz_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->addColumn('question_type', 'enum', ['values' => ['single', 'multiple'], 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('points_correct', 'integer', ['signed' => false, 'null' => false, 'default' => 1])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['quiz_id'])
            ->addForeignKey('quiz_id', 'adms_gamification_quizzes', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_gamification_quiz_options', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('question_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('is_correct', 'boolean', ['default' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['question_id'])
            ->addForeignKey('question_id', 'adms_gamification_quiz_questions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_gamification_quiz_attempts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('quiz_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('status', 'enum', ['values' => ['in_progress', 'completed', 'abandoned'], 'default' => 'in_progress'])
            ->addColumn('score', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('max_score', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('percent', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('points_awarded', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('started_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('finished_at', 'datetime', ['null' => true])
            ->addIndex(['quiz_id', 'user_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('quiz_id', 'adms_gamification_quizzes', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_gamification_quiz_attempt_answers', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('attempt_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('question_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('selected_option_ids_json', 'text', ['null' => false])
            ->addColumn('is_correct', 'boolean', ['default' => false])
            ->addColumn('points_earned', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['attempt_id'])
            ->addIndex(['question_id'])
            ->addIndex(['attempt_id', 'question_id'], ['unique' => true, 'name' => 'uk_gamification_attempt_question'])
            ->addForeignKey('attempt_id', 'adms_gamification_quiz_attempts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('question_id', 'adms_gamification_quiz_questions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_gamification_quiz_attempt_answers')) {
            $this->table('adms_gamification_quiz_attempt_answers')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_quiz_attempts')) {
            $this->table('adms_gamification_quiz_attempts')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_quiz_options')) {
            $this->table('adms_gamification_quiz_options')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_quiz_questions')) {
            $this->table('adms_gamification_quiz_questions')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_quizzes')) {
            $this->table('adms_gamification_quizzes')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_point_ledger')) {
            $this->table('adms_gamification_point_ledger')->drop()->save();
        }
        if ($this->hasTable('adms_gamification_timeline_rules')) {
            $this->table('adms_gamification_timeline_rules')->drop()->save();
        }
    }
}
