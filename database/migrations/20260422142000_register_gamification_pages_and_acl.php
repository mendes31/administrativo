<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registra páginas de gamificação e espelha ACL a partir de referências estáveis (Timeline / Informativos).
 */
final class RegisterGamificationPagesAndAcl extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $timeline = $this->fetchRow("SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'Timeline' LIMIT 1");
        $informativos = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListInformativos' LIMIT 1");
        $timelineAclId = $timeline ? (int)$timeline['id'] : 0;
        $informativosAclId = $informativos ? (int)$informativos['id'] : 0;
        $gid = $timeline ? (int)($timeline['adms_groups_page_id'] ?? 0) : 0;
        if ($gid <= 0) {
            $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Comunicação Social' LIMIT 1");
            $gid = $g ? (int)$g['id'] : 1;
        }

        if ($timelineAclId <= 0 && $informativosAclId <= 0) {
            return;
        }

        $aclAdmin = $informativosAclId > 0 ? $informativosAclId : $timelineAclId;
        $aclEmployee = $timelineAclId > 0 ? $timelineAclId : $informativosAclId;

        $pages = [
            ['ListGamificationTimelineRules', 'list-gamification-timeline-rules', 'Regras de pontos (Timeline)', 'admin', 'Listagem e gestão das regras de pontuação por interação na timeline.'],
            ['UpdateGamificationTimelineRule', 'update-gamification-timeline-rule', 'Editar regra de pontos (Timeline)', 'admin', 'Formulário para ajustar pontos e limites de uma regra.'],
            ['ListGamificationQuizzes', 'list-gamification-quizzes', 'Quizzes (Gamificação)', 'admin', 'CRUD de quizzes de gamificação.'],
            ['CreateGamificationQuiz', 'create-gamification-quiz', 'Criar quiz (Gamificação)', 'admin', ''],
            ['UpdateGamificationQuiz', 'update-gamification-quiz', 'Editar quiz (Gamificação)', 'admin', ''],
            ['DeleteGamificationQuiz', 'delete-gamification-quiz', 'Excluir quiz (Gamificação)', 'admin', ''],
            ['ListGamificationQuizQuestions', 'list-gamification-quiz-questions', 'Questões do quiz (Gamificação)', 'admin', ''],
            ['CreateGamificationQuizQuestion', 'create-gamification-quiz-question', 'Nova questão (Gamificação)', 'admin', ''],
            ['UpdateGamificationQuizQuestion', 'update-gamification-quiz-question', 'Editar questão (Gamificação)', 'admin', ''],
            ['DeleteGamificationQuizQuestion', 'delete-gamification-quiz-question', 'Excluir questão (Gamificação)', 'admin', ''],
            ['ListGamificationPointLedger', 'list-gamification-point-ledger', 'Extrato de pontos (Gamificação)', 'admin', 'Auditoria do ledger de pontos.'],
            ['GamificationQuizCatalog', 'gamification-quiz-catalog', 'Quizzes disponíveis', 'employee', 'Catálogo de quizzes publicados para o colaborador.'],
            ['TakeGamificationQuiz', 'take-gamification-quiz', 'Responder quiz (Gamificação)', 'employee', ''],
            ['SubmitGamificationQuizAttempt', 'submit-gamification-quiz-attempt', 'Enviar respostas do quiz', 'employee', ''],
        ];

        foreach ($pages as $def) {
            [$controller, $slug, $name, $aclKind, $obs] = $def;
            if (!preg_match('/^[A-Za-z0-9_]+$/', $controller) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                continue;
            }
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $name,
                'controller' => $controller,
                'controller_url' => $slug,
                'directory' => 'gamification',
                'obs' => $obs,
                'public_page' => 0,
                'default_page' => 1,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();

            $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
            $newId = (int)($newRow['id'] ?? 0);
            if ($newId <= 0) {
                continue;
            }

            $templateId = ($aclKind === 'employee') ? $aclEmployee : $aclAdmin;
            if ($templateId <= 0) {
                continue;
            }

            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT permission, adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages
                 WHERE adms_page_id = {$templateId}"
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $controllers = [
            'ListGamificationTimelineRules', 'UpdateGamificationTimelineRule',
            'ListGamificationQuizzes', 'CreateGamificationQuiz', 'UpdateGamificationQuiz', 'DeleteGamificationQuiz',
            'ListGamificationQuizQuestions', 'CreateGamificationQuizQuestion', 'UpdateGamificationQuizQuestion', 'DeleteGamificationQuizQuestion',
            'ListGamificationPointLedger', 'GamificationQuizCatalog', 'TakeGamificationQuiz', 'SubmitGamificationQuizAttempt',
        ];
        foreach ($controllers as $c) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $c)) {
                continue;
            }
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$c}' LIMIT 1");
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
