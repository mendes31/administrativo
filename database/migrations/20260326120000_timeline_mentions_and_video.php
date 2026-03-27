<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TimelineMentionsAndVideo extends AbstractMigration
{
    public function up(): void
    {
        $this->table('adms_timeline_posts')
            ->addColumn('video_path', 'string', ['limit' => 512, 'null' => true])
            ->update();

        $mentions = $this->table('adms_timeline_mentions', ['id' => false, 'primary_key' => ['id']]);
        $mentions->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('entity_type', 'enum', [
                'values' => ['post', 'comment'],
                'null' => false,
            ])
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('mentioned_user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['entity_type', 'entity_id'])
            ->addIndex(['mentioned_user_id'])
            ->addIndex(['entity_type', 'entity_id', 'mentioned_user_id'], ['unique' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_timeline_mentions')->drop()->save();

        $this->table('adms_timeline_posts')
            ->removeColumn('video_path')
            ->update();
    }
}
