<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Comitês, arquivamento LGPD e páginas da Fase 2 do Canal de Denúncias.
 */
final class WhistleblowingPhase2 extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $table = $this->table('adms_whistleblowing_reports');
            if (!$table->hasColumn('committee_id')) {
                $table->addColumn('committee_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'assigned_user_id'])
                    ->addColumn('archived_at', 'datetime', ['null' => true, 'after' => 'closed_at'])
                    ->addIndex(['committee_id'])
                    ->addIndex(['archived_at'])
                    ->update();
            }
        }

        if (!$this->hasTable('adms_whistleblowing_committees')) {
            $this->table('adms_whistleblowing_committees')
                ->addColumn('name', 'string', ['limit' => 120])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('is_active', 'boolean', ['default' => 1])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_committee_members')) {
            $this->table('adms_whistleblowing_committee_members')
                ->addColumn('committee_id', 'integer', ['signed' => false])
                ->addColumn('user_id', 'integer', ['signed' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['committee_id'])
                ->addIndex(['user_id'])
                ->addForeignKey('committee_id', 'adms_whistleblowing_committees', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        if (!$this->hasTable('adms_whistleblowing_committee_categories')) {
            $this->table('adms_whistleblowing_committee_categories')
                ->addColumn('committee_id', 'integer', ['signed' => false])
                ->addColumn('category', 'string', ['limit' => 80])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['committee_id'])
                ->addIndex(['category'])
                ->addForeignKey('committee_id', 'adms_whistleblowing_committees', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                ->create();
        }

        if ($this->hasTable('adms_whistleblowing_reports') && $this->hasTable('adms_whistleblowing_committees')) {
            try {
                $this->table('adms_whistleblowing_reports')
                    ->addForeignKey('committee_id', 'adms_whistleblowing_committees', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
                    ->update();
            } catch (\Throwable) {
                // FK já existe ou ambiente sem suporte
            }
        }

        $this->registerPages();
        $this->seedDefaultCommittees();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];
        $now = date('Y-m-d H:i:s');

        $pages = [
            ['name' => 'Dashboard Denúncias', 'controller' => 'WhistleblowingDashboard', 'controller_url' => 'denuncias-dashboard', 'public_page' => 0],
            ['name' => 'Listar Comitês Denúncias', 'controller' => 'WhistleblowingListCommittees', 'controller_url' => 'list-whistleblowing-committees', 'public_page' => 0],
            ['name' => 'Cadastrar Comitê Denúncias', 'controller' => 'WhistleblowingCreateCommittee', 'controller_url' => 'create-whistleblowing-committee', 'public_page' => 0],
            ['name' => 'Editar Comitê Denúncias', 'controller' => 'WhistleblowingUpdateCommittee', 'controller_url' => 'update-whistleblowing-committee', 'public_page' => 0],
            ['name' => 'Cron retenção denúncias (LGPD)', 'controller' => 'WhistleblowingRetentionCron', 'controller_url' => 'whistleblowing-retention-cron', 'public_page' => 1],
        ];

        foreach ($pages as $p) {
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$p['controller']}' LIMIT 1");
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $p['name'],
                'controller' => $p['controller'],
                'controller_url' => $p['controller_url'],
                'directory' => 'whistleblowing',
                'obs' => 'Canal de Denúncias — Fase 2.',
                'public_page' => $p['public_page'],
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            if ((int) $p['public_page'] === 0 && $this->hasTable('adms_access_levels_pages')) {
                $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
                $newId = (int) ($newRow['id'] ?? 0);
                if ($newId > 0) {
                    $this->execute(
                        "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                         SELECT 0, al.id, {$newId}, '{$now}', '{$now}' FROM adms_access_levels al"
                    );
                }
            }
        }
    }

    private function seedDefaultCommittees(): void
    {
        if (!$this->hasTable('adms_whistleblowing_committees')) {
            return;
        }

        $count = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM adms_whistleblowing_committees')['c'];
        if ($count > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $committees = [
            ['name' => 'Comitê de Ética', 'description' => 'Assédio, discriminação, conflito de interesse e conduta ética.', 'categories' => ['Assédio Moral', 'Assédio Sexual', 'Discriminação', 'Conflito de Interesse', 'Outros']],
            ['name' => 'Compliance', 'description' => 'Fraude, corrupção e favorecimento.', 'categories' => ['Fraude', 'Corrupção', 'Favorecimento']],
            ['name' => 'Segurança e Qualidade', 'description' => 'Segurança do trabalho, qualidade e meio ambiente.', 'categories' => ['Segurança', 'Qualidade', 'Meio Ambiente']],
            ['name' => 'Patrimônio', 'description' => 'Furto e danos ao patrimônio.', 'categories' => ['Furto']],
        ];

        foreach ($committees as $c) {
            $this->table('adms_whistleblowing_committees')->insert([
                'name' => $c['name'],
                'description' => $c['description'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            $row = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $cid = (int) ($row['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }

            foreach ($c['categories'] as $cat) {
                $this->table('adms_whistleblowing_committee_categories')->insert([
                    'committee_id' => $cid,
                    'category' => $cat,
                    'created_at' => $now,
                ])->save();
            }
        }
    }

    public function down(): void
    {
        $controllers = [
            'WhistleblowingDashboard',
            'WhistleblowingListCommittees',
            'WhistleblowingCreateCommittee',
            'WhistleblowingUpdateCommittee',
            'WhistleblowingRetentionCron',
        ];

        if ($this->hasTable('adms_pages')) {
            foreach ($controllers as $ctrl) {
                $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
                if (!$row) {
                    continue;
                }
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('adms_whistleblowing_committee_categories')) {
            $this->table('adms_whistleblowing_committee_categories')->drop()->save();
        }
        if ($this->hasTable('adms_whistleblowing_committee_members')) {
            $this->table('adms_whistleblowing_committee_members')->drop()->save();
        }
        if ($this->hasTable('adms_whistleblowing_committees')) {
            $this->table('adms_whistleblowing_committees')->drop()->save();
        }

        if ($this->hasTable('adms_whistleblowing_reports')) {
            $table = $this->table('adms_whistleblowing_reports');
            if ($table->hasColumn('committee_id')) {
                $table->removeColumn('committee_id')->removeColumn('archived_at')->update();
            }
        }
    }
}
