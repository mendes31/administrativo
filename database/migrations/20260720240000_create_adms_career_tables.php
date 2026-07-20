<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsCareerTables extends AbstractMigration
{
    public function up(): void
    {
        $this->createTracks();
        $this->createLevels();
        $this->createPromotions();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach ([
            'ListCareerTracks', 'CreateCareerTrack', 'ViewCareerTrack', 'UpdateCareerTrack',
            'ListCareerPromotions', 'CreateCareerPromotion', 'ViewCareerPromotion', 'UpdateCareerPromotion',
        ] as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        if ($this->hasTable('adms_career_promotions')) {
            $this->table('adms_career_promotions')->drop()->save();
        }
        if ($this->hasTable('adms_career_levels')) {
            $this->table('adms_career_levels')->drop()->save();
        }
        if ($this->hasTable('adms_career_tracks')) {
            $this->table('adms_career_tracks')->drop()->save();
        }
    }

    private function createTracks(): void
    {
        if ($this->hasTable('adms_career_tracks')) {
            return;
        }
        $this->table('adms_career_tracks', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active', 'comment' => 'active|inactive'])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['status'], ['name' => 'idx_career_tracks_status'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createLevels(): void
    {
        if ($this->hasTable('adms_career_levels')) {
            return;
        }
        $this->table('adms_career_levels', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('career_track_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('level_order', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('position_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['career_track_id', 'level_order'], ['name' => 'idx_career_levels_order'])
            ->addForeignKey('career_track_id', 'adms_career_tracks', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('position_id', 'adms_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createPromotions(): void
    {
        if ($this->hasTable('adms_career_promotions')) {
            return;
        }
        $this->table('adms_career_promotions', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('from_position_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('to_position_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('career_track_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('career_level_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('effective_date', 'date', ['null' => false])
            ->addColumn('status', 'string', [
                'limit' => 20, 'default' => 'draft',
                'comment' => 'draft|approved|applied|cancelled',
            ])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('approved_at', 'datetime', ['null' => true])
            ->addColumn('applied_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['user_id'], ['name' => 'idx_career_promo_user'])
            ->addIndex(['status'], ['name' => 'idx_career_promo_status'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('from_position_id', 'adms_positions', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('to_position_id', 'adms_positions', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('career_track_id', 'adms_career_tracks', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('career_level_id', 'adms_career_levels', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('approved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $groupId = 36;
        foreach (['ListCriticalPositions', 'ListTalentNominations', 'ListPerformanceGoals'] as $ref) {
            $group = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = '{$ref}' LIMIT 1");
            if ($group && !empty($group['adms_groups_page_id'])) {
                $groupId = (int) $group['adms_groups_page_id'];
                break;
            }
        }

        $pages = [
            ['Listar Trilhas de Carreira', 'ListCareerTracks', 'list-career-tracks', 'Trilhas de carreira.'],
            ['Criar Trilha de Carreira', 'CreateCareerTrack', 'create-career-track', 'Criar trilha.'],
            ['Visualizar Trilha', 'ViewCareerTrack', 'view-career-track', 'Detalhe e níveis.'],
            ['Editar Trilha', 'UpdateCareerTrack', 'update-career-track', 'Editar trilha.'],
            ['Listar Promoções', 'ListCareerPromotions', 'list-career-promotions', 'Promoções de carreira.'],
            ['Criar Promoção', 'CreateCareerPromotion', 'create-career-promotion', 'Registrar promoção.'],
            ['Visualizar Promoção', 'ViewCareerPromotion', 'view-career-promotion', 'Detalhe da promoção.'],
            ['Editar Promoção', 'UpdateCareerPromotion', 'update-career-promotion', 'Editar/aprovar/aplicar promoção.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');
        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
            if ($existing) {
                $this->grant((int) $existing['id']);
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($name) . ', ' . $conn->quote($controller) . ', ' . $conn->quote($url) . ', '
                . $conn->quote('performance') . ', ' . $conn->quote($obs) . ', 0, 0, 1, 1, '
                . $groupId . ', ' . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
            );
            $page = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
            if ($page) {
                $this->grant((int) $page['id']);
            }
        }
    }

    private function grant(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $ref = null;
        foreach (['ListCriticalPositions', 'ListTalentNominations', 'ListPerformanceGoals'] as $ctrl) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if ($ref) {
                break;
            }
        }
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
