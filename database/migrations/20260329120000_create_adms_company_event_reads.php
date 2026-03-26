<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsCompanyEventReads extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('adms_company_event_reads', [
            'id' => false,
            'primary_key' => ['id']
        ]);

        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('event_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('read_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['event_id', 'user_id'], ['unique' => true, 'name' => 'uq_company_event_read_user'])
            ->addIndex(['user_id'], ['name' => 'idx_company_event_reads_user'])
            ->create();

        $this->execute('ALTER TABLE adms_company_event_reads ADD CONSTRAINT fk_company_event_reads_event FOREIGN KEY (event_id) REFERENCES adms_company_events(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->execute('ALTER TABLE adms_company_event_reads ADD CONSTRAINT fk_company_event_reads_user FOREIGN KEY (user_id) REFERENCES adms_users(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE adms_company_event_reads DROP FOREIGN KEY fk_company_event_reads_event');
        $this->execute('ALTER TABLE adms_company_event_reads DROP FOREIGN KEY fk_company_event_reads_user');
        $this->table('adms_company_event_reads')->drop()->save();
    }
}

