<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelinePolls extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_posts')
            ->addColumn('post_type', 'enum', [
                'values' => ['regular', 'poll'],
                'default' => 'regular',
                'after' => 'shared_from_post_id',
            ])
            ->addIndex(['post_type'])
            ->update();

        $this->table('adms_timeline_polls', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('question', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('starts_at', 'datetime', ['null' => true])
            ->addColumn('ends_at', 'datetime', ['null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'], ['unique' => true])
            ->addIndex(['starts_at'])
            ->addIndex(['ends_at'])
            ->addForeignKey('post_id', 'adms_timeline_posts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_timeline_poll_options', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('poll_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('option_text', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['poll_id'])
            ->addForeignKey('poll_id', 'adms_timeline_polls', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->table('adms_timeline_poll_votes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('poll_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('option_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['poll_id'])
            ->addIndex(['option_id'])
            ->addIndex(['user_id'])
            ->addIndex(['poll_id', 'user_id'], ['unique' => true])
            ->addForeignKey('poll_id', 'adms_timeline_polls', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('option_id', 'adms_timeline_poll_options', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_timeline_poll_votes')->drop()->save();
        $this->table('adms_timeline_poll_options')->drop()->save();
        $this->table('adms_timeline_polls')->drop()->save();
        $this->table('adms_timeline_posts')
            ->removeIndex(['post_type'])
            ->removeColumn('post_type')
            ->update();
    }
}

