<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineSharedPosts extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_posts')
            ->addColumn('shared_from_post_id', 'integer', ['signed' => false, 'null' => true, 'after' => 'video_path'])
            ->addIndex(['shared_from_post_id'])
            ->addForeignKey('shared_from_post_id', 'adms_timeline_posts', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->update();
    }

    public function down(): void
    {
        $this->table('adms_timeline_posts')
            ->dropForeignKey('shared_from_post_id')
            ->removeIndex(['shared_from_post_id'])
            ->removeColumn('shared_from_post_id')
            ->update();
    }
}

