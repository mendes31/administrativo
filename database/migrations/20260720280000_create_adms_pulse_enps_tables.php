<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsPulseEnpsTables extends AbstractMigration
{
    public function up(): void
    {
        $this->createCampaigns();
        $this->createQuestions();
        $this->createResponses();
        $this->registerPages();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        foreach ([
            'ListPulseCampaigns', 'CreatePulseCampaign', 'ViewPulseCampaign',
            'UpdatePulseCampaign', 'RespondPulseCampaign',
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
        if ($this->hasTable('adms_pulse_responses')) {
            $this->table('adms_pulse_responses')->drop()->save();
        }
        if ($this->hasTable('adms_pulse_questions')) {
            $this->table('adms_pulse_questions')->drop()->save();
        }
        if ($this->hasTable('adms_pulse_campaigns')) {
            $this->table('adms_pulse_campaigns')->drop()->save();
        }
        $this->bumpMenuCache();
    }

    private function createCampaigns(): void
    {
        if ($this->hasTable('adms_pulse_campaigns')) {
            return;
        }
        $this->table('adms_pulse_campaigns', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('campaign_type', 'string', ['limit' => 20, 'default' => 'enps', 'comment' => 'enps|pulse'])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft', 'comment' => 'draft|open|closed'])
            ->addColumn('starts_at', 'date', ['null' => true])
            ->addColumn('ends_at', 'date', ['null' => true])
            ->addColumn('anonymous', 'boolean', ['default' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['status'], ['name' => 'idx_pulse_campaigns_status'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createQuestions(): void
    {
        if ($this->hasTable('adms_pulse_questions')) {
            return;
        }
        $this->table('adms_pulse_questions', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('campaign_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('question_text', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('question_type', 'string', ['limit' => 20, 'default' => 'nps', 'comment' => 'nps|likert|text'])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['campaign_id', 'sort_order'], ['name' => 'idx_pulse_questions_order'])
            ->addForeignKey('campaign_id', 'adms_pulse_campaigns', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function createResponses(): void
    {
        if ($this->hasTable('adms_pulse_responses')) {
            return;
        }
        $this->table('adms_pulse_responses', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('campaign_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('question_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('score', 'integer', ['signed' => true, 'null' => true])
            ->addColumn('comment_text', 'text', ['null' => true])
            ->addColumn('answered_at', 'datetime', ['null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['campaign_id'], ['name' => 'idx_pulse_resp_campaign'])
            ->addIndex(['question_id', 'user_id'], ['name' => 'idx_pulse_resp_question_user'])
            ->addForeignKey('campaign_id', 'adms_pulse_campaigns', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('question_id', 'adms_pulse_questions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $groupId = 36;
        foreach (['PeopleAnalytics', 'ListPerformanceGoals', 'ListPerformanceReviews'] as $ref) {
            $group = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = '{$ref}' LIMIT 1");
            if ($group && !empty($group['adms_groups_page_id'])) {
                $groupId = (int) $group['adms_groups_page_id'];
                break;
            }
        }

        $pages = [
            ['Listar Pesquisas Pulse/eNPS', 'ListPulseCampaigns', 'list-pulse-campaigns', 'Campanhas de clima/pulse/eNPS.'],
            ['Criar Pesquisa Pulse/eNPS', 'CreatePulseCampaign', 'create-pulse-campaign', 'Criar campanha.'],
            ['Visualizar Pesquisa Pulse/eNPS', 'ViewPulseCampaign', 'view-pulse-campaign', 'Detalhe e resumo eNPS.'],
            ['Editar Pesquisa Pulse/eNPS', 'UpdatePulseCampaign', 'update-pulse-campaign', 'Editar/abrir/fechar campanha.'],
            ['Responder Pesquisa Pulse/eNPS', 'RespondPulseCampaign', 'respond-pulse-campaign', 'Resposta do colaborador.'],
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
                . $conn->quote('analytics') . ', ' . $conn->quote($obs) . ', 0, 0, 1, 1, '
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
        foreach (['PeopleAnalytics', 'ListPerformanceGoals', 'ListPerformanceReviews'] as $ctrl) {
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

    private function bumpMenuCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
