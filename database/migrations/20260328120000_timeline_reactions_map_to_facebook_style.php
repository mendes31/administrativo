<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineReactionsMapToFacebookStyle extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'love' WHERE reaction_type = 'heart'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'haha' WHERE reaction_type = 'celebrate'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'care' WHERE reaction_type = 'clap'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'wow' WHERE reaction_type = 'insight'");
    }

    public function down(): void
    {
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'heart' WHERE reaction_type = 'love'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'celebrate' WHERE reaction_type = 'haha'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'clap' WHERE reaction_type = 'care'");
        $this->execute("UPDATE adms_timeline_likes SET reaction_type = 'insight' WHERE reaction_type = 'wow'");
    }
}
