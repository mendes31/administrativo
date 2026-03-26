<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelinePostImages extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_post_images', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('image_path', 'string', ['limit' => 512, 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'])
            ->addForeignKey('post_id', 'adms_timeline_posts', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_timeline_post_images')->drop()->save();
    }
}

