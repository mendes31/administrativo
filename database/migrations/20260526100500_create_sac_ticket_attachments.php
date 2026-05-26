<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacTicketAttachments extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_ticket_attachments')) {
            return;
        }

        $table = $this->table('sac_ticket_attachments');

        $table->addColumn('ticket_id', 'integer', ['signed' => false])
              ->addColumn('message_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('file_name', 'string', ['limit' => 255])
              ->addColumn('file_path', 'string', ['limit' => 500])
              ->addColumn('file_size', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('file_type', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('uploaded_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['ticket_id'])
              ->addIndex(['message_id'])

              ->addForeignKey('ticket_id', 'sac_tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('message_id', 'sac_ticket_messages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
              ->addForeignKey('uploaded_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
