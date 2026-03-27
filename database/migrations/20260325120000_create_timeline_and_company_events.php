<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTimelineAndCompanyEvents extends AbstractMigration
{
    public function up(): void
    {
        $posts = $this->table('adms_timeline_posts', ['id' => false, 'primary_key' => ['id']]);
        $posts->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('image_path', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('status', 'enum', [
                'values' => ['active', 'hidden'],
                'default' => 'active',
            ])
            ->addColumn('hidden_reason', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('hidden_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['user_id'])
            ->addIndex(['status'])
            ->addIndex(['created_at'])
            ->create();

        $likes = $this->table('adms_timeline_likes', ['id' => false, 'primary_key' => ['id']]);
        $likes->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'])
            ->addIndex(['user_id'])
            ->addIndex(['post_id', 'user_id'], ['unique' => true])
            ->create();

        $comments = $this->table('adms_timeline_comments', ['id' => false, 'primary_key' => ['id']]);
        $comments->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('content', 'string', ['limit' => 2000, 'null' => false])
            ->addColumn('status', 'enum', [
                'values' => ['active', 'hidden'],
                'default' => 'active',
            ])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'])
            ->addIndex(['user_id'])
            ->create();

        $reports = $this->table('adms_timeline_reports', ['id' => false, 'primary_key' => ['id']]);
        $reports->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('post_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('reporter_user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('details', 'text', ['null' => true])
            ->addColumn('status', 'enum', [
                'values' => ['open', 'reviewed', 'dismissed'],
                'default' => 'open',
            ])
            ->addColumn('reviewed_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('reviewed_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['post_id'])
            ->addIndex(['status'])
            ->create();

        $events = $this->table('adms_company_events', ['id' => false, 'primary_key' => ['id']]);
        $events->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('location', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('starts_at', 'datetime', ['null' => false])
            ->addColumn('ends_at', 'datetime', ['null' => false])
            ->addColumn('publish_at', 'datetime', ['null' => true])
            ->addColumn('expire_at', 'datetime', ['null' => true])
            ->addColumn('rsvp_deadline', 'datetime', ['null' => true])
            ->addColumn('cancellation_deadline', 'datetime', ['null' => true])
            ->addColumn('requires_rsvp', 'boolean', ['default' => false])
            ->addColumn('allows_guests', 'boolean', ['default' => false])
            ->addColumn('max_guests_per_user', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('department_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ativo', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['starts_at'])
            ->addIndex(['ativo'])
            ->addIndex(['created_by'])
            ->create();

        $rsvps = $this->table('adms_company_event_rsvps', ['id' => false, 'primary_key' => ['id']]);
        $rsvps->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('event_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('status', 'enum', [
                'values' => ['pending', 'confirmed', 'declined', 'cancelled'],
                'default' => 'pending',
            ])
            ->addColumn('responded_at', 'datetime', ['null' => true])
            ->addColumn('cancelled_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['event_id'])
            ->addIndex(['user_id'])
            ->addIndex(['event_id', 'user_id'], ['unique' => true])
            ->create();

        $guests = $this->table('adms_company_event_guests', ['id' => false, 'primary_key' => ['id']]);
        $guests->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rsvp_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('full_name', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('relationship', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('age', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('notes', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['rsvp_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('adms_company_event_guests')->drop()->save();
        $this->table('adms_company_event_rsvps')->drop()->save();
        $this->table('adms_company_events')->drop()->save();
        $this->table('adms_timeline_reports')->drop()->save();
        $this->table('adms_timeline_comments')->drop()->save();
        $this->table('adms_timeline_likes')->drop()->save();
        $this->table('adms_timeline_posts')->drop()->save();
    }
}
