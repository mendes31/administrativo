<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Card de atalho para quizzes no dashboard (ACL fechada por defeito) e página pública de ranking (ACL espelha Timeline).
 */
final class RegisterDashboardCardQuizzesAndLeaderboard extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'DashboardCardMyCalendar' LIMIT 1");
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'DashboardCardTimeline' LIMIT 1");
        }
        if (!$ref) {
            $ref = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'Dashboard' LIMIT 1");
        }
        if (!$ref) {
            return;
        }
        $gid = (int)($ref['adms_groups_page_id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        // --- Card dashboard: sem autorização nos níveis (permission=0); super admin / super usuário vê via hasFullSystemAccess ---
        $card = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'DashboardCardGamificationQuizzes' LIMIT 1");
        if (!$card) {
            $this->table('adms_pages')->insert([
                'name' => 'Card Dashboard - Quizzes (Gamificação)',
                'controller' => 'DashboardCardGamificationQuizzes',
                'controller_url' => 'dashboard-card-gamification-quizzes',
                'directory' => 'dashboard',
                'obs' => 'Controla visibilidade do card de Quizzes disponíveis no dashboard.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
            $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $cardId = (int)($newRow['id'] ?? 0);
            if ($cardId > 0 && $this->hasTable('adms_access_levels_pages')) {
                $this->execute(
                    "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$cardId}, '{$now}', '{$now}'
                     FROM adms_access_levels al"
                );
            }
        }

        // --- Ranking: mesma matriz de permissões da Timeline (colaboradores com timeline veem ranking) ---
        $existsLb = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'GamificationLeaderboard' LIMIT 1");
        if (!$existsLb) {
            $timeline = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'Timeline' LIMIT 1");
            $gSocial = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Comunicação Social' LIMIT 1");
            $gid2 = $gSocial ? (int)$gSocial['id'] : $gid;

            $this->table('adms_pages')->insert([
                'name' => 'Ranking de pontos (Gamificação)',
                'controller' => 'GamificationLeaderboard',
                'controller_url' => 'gamification-leaderboard',
                'directory' => 'gamification',
                'obs' => 'Classificação por pontos registados no ledger de gamificação.',
                'public_page' => 0,
                'default_page' => 1,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid2,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
            $lbRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $lbId = (int)($lbRow['id'] ?? 0);
            if ($lbId > 0 && $this->hasTable('adms_access_levels_pages') && $timeline) {
                $tid = (int)$timeline['id'];
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT permission, adms_access_level_id, {$lbId}, '{$now}', '{$now}'
                     FROM adms_access_levels_pages
                     WHERE adms_page_id = {$tid}"
                );
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        foreach (['DashboardCardGamificationQuizzes', 'GamificationLeaderboard'] as $ctrl) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $ctrl)) {
                continue;
            }
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int)$row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }
    }
}
