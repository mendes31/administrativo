<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineCommentReactions extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_comment_likes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('comment_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('reaction_type', 'string', ['limit' => 20, 'default' => 'like'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['comment_id'])
            ->addIndex(['user_id'])
            ->addIndex(['comment_id', 'user_id'], ['unique' => true])
            ->addForeignKey('comment_id', 'adms_timeline_comments', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_timeline_comment_likes')->drop()->save();
    }
}
