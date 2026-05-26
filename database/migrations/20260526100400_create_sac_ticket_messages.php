<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacTicketMessages extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_ticket_messages')) {
            return;
        }

        $table = $this->table('sac_ticket_messages');

        $table->addColumn('ticket_id', 'integer', ['signed' => false])
              ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('sender_type', 'enum', ['values' => ['agent', 'client', 'system'], 'default' => 'agent'])
              ->addColumn('message', 'text')
              ->addColumn('is_internal_note', 'boolean', ['default' => 0])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['ticket_id'])
              ->addIndex(['user_id'])
              ->addIndex(['sender_type'])

              ->addForeignKey('ticket_id', 'sac_tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
