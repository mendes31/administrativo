<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineFeaturedAndCelebrationTypes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_timeline_posts')) {
            return;
        }

        $this->execute(
            "ALTER TABLE adms_timeline_posts
             MODIFY COLUMN post_type ENUM('regular', 'poll', 'birthday', 'tenure') NOT NULL DEFAULT 'regular'"
        );

        if (!$this->table('adms_timeline_posts')->hasColumn('is_featured')) {
            $this->table('adms_timeline_posts')
                ->addColumn('is_featured', 'boolean', [
                    'default' => false,
                    'null' => false,
                    'after' => 'post_type',
                ])
                ->addIndex(['is_featured'])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_timeline_posts')) {
            return;
        }

        if ($this->table('adms_timeline_posts')->hasColumn('is_featured')) {
            $this->table('adms_timeline_posts')
                ->removeIndex(['is_featured'])
                ->removeColumn('is_featured')
                ->update();
        }

        $this->execute(
            "UPDATE adms_timeline_posts SET post_type = 'regular'
             WHERE post_type IN ('birthday', 'tenure')"
        );
        $this->execute(
            "ALTER TABLE adms_timeline_posts
             MODIFY COLUMN post_type ENUM('regular', 'poll') NOT NULL DEFAULT 'regular'"
        );
    }
}
