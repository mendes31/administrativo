<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineHashtags extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('tag', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['tag'], ['unique' => true])
            ->create();

        $this->table('adms_timeline_post_tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('tag_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'])
            ->addIndex(['tag_id'])
            ->addIndex(['post_id', 'tag_id'], ['unique' => true])
            ->addForeignKey('post_id', 'adms_timeline_posts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('tag_id', 'adms_timeline_tags', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_timeline_post_tags')->drop()->save();
        $this->table('adms_timeline_tags')->drop()->save();
    }
}

