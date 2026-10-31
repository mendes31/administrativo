<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Convidados de fora da empresa, com link aberto de confirmação.
 * Não usa a lista de acompanhantes do colaborador (adms_company_event_guests).
 */
final class CreateCompanyEventExternalInvites extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_company_events') && !$this->hasTable('adms_company_event_external_invites')) {
            $this->table('adms_company_event_external_invites', ['id' => false, 'primary_key' => ['id']])
                ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
                ->addColumn('event_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('full_name', 'string', ['limit' => 200, 'null' => false])
                ->addColumn('invite_reason', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('phone', 'string', ['limit' => 20, 'null' => false])
                ->addColumn('status', 'enum', [
                    'values' => ['pending', 'confirmed', 'declined'],
                    'default' => 'pending',
                    'null' => false,
                ])
                ->addColumn('token', 'char', ['limit' => 64, 'null' => false])
                ->addColumn('responded_at', 'datetime', ['null' => true])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['token'], ['unique' => true])
                ->addIndex(['event_id', 'phone'], ['unique' => true])
                ->addIndex(['event_id'])
                ->addForeignKey('event_id', 'adms_company_events', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }
        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'convite-evento' LIMIT 1");
        if ($exists) {
            return;
        }
        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Comunicação Social' LIMIT 1");
        if (!$g) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->table('adms_pages')->insert([
            'name' => 'Convite externo de evento',
            'controller' => 'CompanyEventInvite',
            'controller_url' => 'convite-evento',
            'directory' => 'companyEvents',
            'obs' => 'Página aberta do convite para quem não é colaborador confirmar ou recusar presença.',
            'public_page' => 1,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => (int) $g['id'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute("DELETE FROM adms_pages WHERE controller_url = 'convite-evento' LIMIT 1");
        }
        if ($this->hasTable('adms_company_event_external_invites')) {
            $this->table('adms_company_event_external_invites')->drop()->save();
        }
    }
}
