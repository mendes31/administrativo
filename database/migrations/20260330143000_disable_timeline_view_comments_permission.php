<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DisableTimelineViewCommentsPermission extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("UPDATE adms_pages SET page_status = 0 WHERE controller = 'TimelineViewComments'");
    }

    public function down(): void
    {
        $this->execute("UPDATE adms_pages SET page_status = 1 WHERE controller = 'TimelineViewComments'");
    }
}
