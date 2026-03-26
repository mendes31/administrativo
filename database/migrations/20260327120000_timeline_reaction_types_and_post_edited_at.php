<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineReactionTypesAndPostEditedAt extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_posts')
            ->addColumn('edited_at', 'datetime', ['null' => true])
            ->update();

        $this->table('adms_timeline_likes')
            ->addColumn('reaction_type', 'string', ['limit' => 32, 'default' => 'heart', 'null' => false])
            ->update();

        $this->execute('UPDATE adms_timeline_likes SET reaction_type = "heart" WHERE reaction_type IS NULL OR reaction_type = ""');
    }

    public function down(): void
    {
        $this->table('adms_timeline_likes')
            ->removeColumn('reaction_type')
            ->update();

        $this->table('adms_timeline_posts')
            ->removeColumn('edited_at')
            ->update();
    }
}
