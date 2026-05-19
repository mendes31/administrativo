<?php

declare(strict_types=1);

use App\adms\Database\BaseMigration;

final class CreateAdmsCompanyEventImages extends BaseMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_company_event_images')) {
            return;
        }

        $this->table('adms_company_event_images', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('event_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('image_path', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['event_id'])
            ->addIndex(['event_id', 'sort_order'])
            ->addForeignKey('event_id', 'adms_company_events', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_company_event_images')) {
            $this->table('adms_company_event_images')->drop()->save();
        }
    }
}
