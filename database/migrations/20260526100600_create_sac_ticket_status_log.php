<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacTicketStatusLog extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_ticket_status_log')) {
            return;
        }

        $table = $this->table('sac_ticket_status_log');

        $table->addColumn('ticket_id', 'integer', ['signed' => false])
              ->addColumn('from_status', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('to_status', 'string', ['limit' => 50])
              ->addColumn('from_user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('to_user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('changed_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['ticket_id'])
              ->addIndex(['changed_by'])
              ->addIndex(['created_at'])

              ->addForeignKey('ticket_id', 'sac_tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('changed_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
