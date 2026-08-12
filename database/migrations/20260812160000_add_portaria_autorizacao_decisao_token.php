<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Token público para anfitrião autorizar/recusar visita sem login (WhatsApp/push).
 */
final class AddPortariaAutorizacaoDecisaoToken extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('portaria_autorizacoes')) {
            $table = $this->table('portaria_autorizacoes');
            if (!$table->hasColumn('decisao_token')) {
                $table
                    ->addColumn('decisao_token', 'string', [
                        'limit' => 64,
                        'null' => true,
                        'default' => null,
                        'after' => 'autorizado_por_user_id',
                    ])
                    ->addColumn('decisao_token_em', 'datetime', [
                        'null' => true,
                        'default' => null,
                        'after' => 'decisao_token',
                    ])
                    ->addIndex(['decisao_token'], ['unique' => true, 'name' => 'uq_portaria_aut_decisao_token'])
                    ->update();
            }
        }

        $this->ensurePublicPage();
    }

    public function down(): void
    {
        $conn = $this->getAdapter()->getConnection();
        $this->execute(
            'DELETE FROM adms_pages WHERE controller_url = ' . $conn->quote('portaria-autorizacao-decisao')
        );

        if (!$this->hasTable('portaria_autorizacoes')) {
            return;
        }
        try {
            $this->execute('ALTER TABLE portaria_autorizacoes DROP INDEX uq_portaria_aut_decisao_token');
        } catch (\Throwable) {
        }
        $table = $this->table('portaria_autorizacoes');
        $changed = false;
        if ($table->hasColumn('decisao_token_em')) {
            $table->removeColumn('decisao_token_em');
            $changed = true;
        }
        if ($table->hasColumn('decisao_token')) {
            $table->removeColumn('decisao_token');
            $changed = true;
        }
        if ($changed) {
            $table->update();
        }
    }

    private function ensurePublicPage(): void
    {
        $conn = $this->getAdapter()->getConnection();
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller_url = 'portaria-autorizacao-decisao' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $group = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote('Portaria') . ' LIMIT 1'
        );
        $gid = $group ? (int) $group['id'] : 1;
        $now = date('Y-m-d H:i:s');

        $this->table('adms_pages')->insert([
            'name' => 'Decisão pública autorização Portaria',
            'controller' => 'PortariaAutorizacaoDecisao',
            'controller_url' => 'portaria-autorizacao-decisao',
            'directory' => 'portaria',
            'obs' => 'Anfitrião autoriza ou recusa visita sem login (link WhatsApp/push).',
            'public_page' => 1,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();
    }
}
