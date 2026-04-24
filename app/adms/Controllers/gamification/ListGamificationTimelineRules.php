<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;
use App\adms\Views\Services\LoadViewService;

class ListGamificationTimelineRules
{
    private array|string|null $data = null;
    /**
     * @return array<string,string>
     */
    private function badgeCriteriaOptions(): array
    {
        return [
            'total_points' => 'Pontuação total',
            'monthly_points' => 'Pontuação mensal',
            'monthly_missions_completed' => 'Missões mensais concluídas',
            'monthly_posts' => 'Publicações no mês',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function missionEventOptions(): array
    {
        return [
            'timeline_post_created' => 'Publicação criada',
            'timeline_share_created' => 'Compartilhamento',
            'timeline_comment_created' => 'Comentário',
            'timeline_reaction_created' => 'Reação',
            'timeline_poll_vote' => 'Voto em enquete',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function levelColorOptions(): array
    {
        return [
            'secondary' => 'Cinza',
            'info' => 'Azul claro',
            'primary' => 'Azul',
            'warning' => 'Amarelo',
            'success' => 'Verde',
            'danger' => 'Vermelho',
            'dark' => 'Escuro',
        ];
    }

    private function slugify(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $map = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a',
            'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
            'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
            'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o',
            'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','Ç'=>'c',
        ];
        $text = strtr($text, $map);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        return mb_substr($text, 0, 140);
    }

    private function validateBadgeCriteriaJson(string $criteriaKey, string $criteriaValueJson, ?string &$errorMessage = null): bool
    {
        $decoded = json_decode($criteriaValueJson, true);
        if (!is_array($decoded)) {
            $errorMessage = 'Valor JSON da badge deve ser um objeto JSON válido.';
            return false;
        }

        if ($criteriaKey === 'total_points' || $criteriaKey === 'monthly_points') {
            if (!array_key_exists('min_points', $decoded) || (int)$decoded['min_points'] < 0) {
                $errorMessage = 'Para este critério, informe "min_points" com valor numérico >= 0.';
                return false;
            }
            return true;
        }

        if ($criteriaKey === 'monthly_missions_completed' || $criteriaKey === 'weekly_missions_completed') {
            if (!array_key_exists('min_missions', $decoded) || (int)$decoded['min_missions'] < 0) {
                $errorMessage = 'Para este critério, informe "min_missions" com valor numérico >= 0.';
                return false;
            }
            return true;
        }

        if ($criteriaKey === 'monthly_posts') {
            if (!array_key_exists('min_posts', $decoded) || (int)$decoded['min_posts'] < 0) {
                $errorMessage = 'Para este critério, informe "min_posts" com valor numérico >= 0.';
                return false;
            }
            return true;
        }

        $errorMessage = 'Critério de badge inválido.';
        return false;
    }

    private function criteriaThresholdKey(string $criteriaKey): string
    {
        return match ($criteriaKey) {
            'total_points', 'monthly_points' => 'min_points',
            'monthly_missions_completed', 'weekly_missions_completed' => 'min_missions',
            'monthly_posts' => 'min_posts',
            default => 'min_points',
        };
    }

    private function buildCriteriaJsonFromThreshold(string $criteriaKey, int $threshold): string
    {
        $payload = [$this->criteriaThresholdKey($criteriaKey) => max(0, $threshold)];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '{}';
    }

    private function extractThresholdFromJson(string $criteriaKey, string $criteriaValueJson): int
    {
        $decoded = json_decode($criteriaValueJson, true);
        if (!is_array($decoded)) {
            return 0;
        }
        $key = $this->criteriaThresholdKey($criteriaKey);
        return max(0, (int)($decoded[$key] ?? 0));
    }

    public function index(): void
    {
        $programRepo = new GamificationProgramRepository();
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $postedSection = (string)($_POST['section'] ?? '');
            $this->handlePost($programRepo);
            $tab = self::redirectHashForGamificationSection($postedSection);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-timeline-rules' . $tab);
            exit;
        }

        $repo = new GamificationTimelineRulesRepository();
        $this->data['rules'] = $repo->listAll();
        $this->data['levels'] = $programRepo->listLevels();
        $allowedLevelColors = array_keys($this->levelColorOptions());
        foreach ($this->data['levels'] as &$levelRow) {
            $color = (string)($levelRow['badge_color'] ?? '');
            if (!in_array($color, $allowedLevelColors, true)) {
                $levelRow['badge_color'] = 'secondary';
            }
        }
        unset($levelRow);
        $this->data['badges'] = $programRepo->listBadges();
        foreach ($this->data['badges'] as &$badgeRow) {
            $criteriaKey = (string)($badgeRow['criteria_key'] ?? '');
            $criteriaJson = (string)($badgeRow['criteria_value_json'] ?? '{}');
            $badgeRow['criteria_threshold'] = $this->extractThresholdFromJson($criteriaKey, $criteriaJson);
            $badgeRow['criteria_threshold_key'] = $this->criteriaThresholdKey($criteriaKey);
        }
        unset($badgeRow);
        $this->data['missions'] = $programRepo->listWeeklyMissions();
        $allowedMissionKeys = array_keys($this->missionEventOptions());
        foreach ($this->data['missions'] as &$missionRow) {
            $missionKey = (string)($missionRow['event_key'] ?? '');
            if (!in_array($missionKey, $allowedMissionKeys, true)) {
                $missionRow['event_key'] = 'timeline_comment_created';
            }
        }
        unset($missionRow);
        $this->data['settings'] = $programRepo->listSettings();
        $this->data['badge_criteria_options'] = $this->badgeCriteriaOptions();
        $this->data['mission_event_options'] = $this->missionEventOptions();
        $this->data['level_color_options'] = $this->levelColorOptions();

        $pageElements = [
            'title_head' => 'Gamificação — Regras da timeline',
            'menu' => 'ListGamificationTimelineRules',
            'buttonPermission' => [
                'UpdateGamificationTimelineRule',
                'ListGamificationTimelineRules',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/list_timeline_rules', $this->data);
        $loadView->loadView();
    }

    private function handlePost(GamificationProgramRepository $programRepo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_gamification_settings', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $section = (string)($_POST['section'] ?? '');
        if ($section === 'level') {
            $badgeColor = trim((string)($_POST['badge_color'] ?? 'secondary'));
            $allowedColors = array_keys($this->levelColorOptions());
            if (!in_array($badgeColor, $allowedColors, true)) {
                $_SESSION['error'] = 'Cor do nível inválida. Selecione uma opção permitida.';
                return;
            }
            $programRepo->updateLevel((int)($_POST['id'] ?? 0), [
                'name' => (string)($_POST['name'] ?? ''),
                'min_points' => (int)($_POST['min_points'] ?? 0),
                'badge_color' => $badgeColor,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'is_active' => (int)($_POST['is_active'] ?? 0) === 1,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Nível atualizado com sucesso.</div>';
            return;
        }
        if ($section === 'level_create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $badgeColor = trim((string)($_POST['badge_color'] ?? 'secondary'));
            $allowedColors = array_keys($this->levelColorOptions());
            if ($name === '') {
                $_SESSION['error'] = 'Informe o nome do nível.';
                return;
            }
            if (!in_array($badgeColor, $allowedColors, true)) {
                $_SESSION['error'] = 'Cor do nível inválida. Selecione uma opção permitida.';
                return;
            }
            $programRepo->createLevel([
                'name' => $name,
                'min_points' => (int)($_POST['min_points'] ?? 0),
                'badge_color' => $badgeColor,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'is_active' => true,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Nível criado com sucesso.</div>';
            return;
        }
        if ($section === 'level_remove') {
            $programRepo->deactivateLevel((int)($_POST['id'] ?? 0));
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Nível removido (desativado) com sucesso.</div>';
            return;
        }
        if ($section === 'badge') {
            $name = trim((string)($_POST['name'] ?? ''));
            $slug = $this->slugify((string)($_POST['slug'] ?? $name));
            $criteriaKey = trim((string)($_POST['criteria_key'] ?? ''));
            $allowedCriteria = array_keys($this->badgeCriteriaOptions());
            if ($name === '' || $slug === '') {
                $_SESSION['error'] = 'Informe nome e slug válidos para a badge.';
                return;
            }
            if (!in_array($criteriaKey, $allowedCriteria, true)) {
                $_SESSION['error'] = 'Critério da badge inválido. Selecione uma opção permitida.';
                return;
            }
            $criteriaValueJson = trim((string)($_POST['criteria_value_json'] ?? '{}'));
            if (isset($_POST['criteria_threshold']) && (string)$_POST['criteria_threshold'] !== '') {
                $criteriaValueJson = $this->buildCriteriaJsonFromThreshold(
                    $criteriaKey,
                    (int)($_POST['criteria_threshold'] ?? 0)
                );
            }
            $errorMessage = null;
            if (!$this->validateBadgeCriteriaJson($criteriaKey, $criteriaValueJson, $errorMessage)) {
                $_SESSION['error'] = $errorMessage ?? 'JSON inválido no campo "Valor JSON" da badge.';
                return;
            }
            if ($programRepo->badgeSlugExists($slug, (int)($_POST['id'] ?? 0))) {
                $_SESSION['error'] = 'Já existe uma badge com esse slug.';
                return;
            }
            $programRepo->updateBadge((int)($_POST['id'] ?? 0), [
                'name' => $name,
                'slug' => $slug,
                'description' => (string)($_POST['description'] ?? ''),
                'criteria_key' => $criteriaKey,
                'criteria_value_json' => $criteriaValueJson,
                'icon' => (string)($_POST['icon'] ?? ''),
                'is_active' => (int)($_POST['is_active'] ?? 0) === 1,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Badge atualizada com sucesso.</div>';
            return;
        }
        if ($section === 'badge_create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $slug = $this->slugify((string)($_POST['slug'] ?? $name));
            $criteriaKey = trim((string)($_POST['criteria_key'] ?? 'total_points'));
            $allowedCriteria = array_keys($this->badgeCriteriaOptions());
            if ($name === '' || $slug === '') {
                $_SESSION['error'] = 'Informe nome para criar a badge.';
                return;
            }
            if (!in_array($criteriaKey, $allowedCriteria, true)) {
                $_SESSION['error'] = 'Critério da badge inválido. Selecione uma opção permitida.';
                return;
            }
            $criteriaValueJson = trim((string)($_POST['criteria_value_json'] ?? '{}'));
            if (isset($_POST['criteria_threshold']) && (string)$_POST['criteria_threshold'] !== '') {
                $criteriaValueJson = $this->buildCriteriaJsonFromThreshold(
                    $criteriaKey,
                    (int)($_POST['criteria_threshold'] ?? 0)
                );
            }
            $errorMessage = null;
            if (!$this->validateBadgeCriteriaJson($criteriaKey, $criteriaValueJson, $errorMessage)) {
                $_SESSION['error'] = $errorMessage ?? 'JSON inválido no campo "Valor JSON" da nova badge.';
                return;
            }
            if ($programRepo->badgeSlugExists($slug)) {
                $_SESSION['error'] = 'Já existe uma badge com esse slug.';
                return;
            }
            $programRepo->createBadge([
                'name' => $name,
                'slug' => $slug,
                'description' => (string)($_POST['description'] ?? ''),
                'criteria_key' => $criteriaKey,
                'criteria_value_json' => $criteriaValueJson,
                'icon' => (string)($_POST['icon'] ?? 'fa-award'),
                'is_active' => true,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Badge criada com sucesso.</div>';
            return;
        }
        if ($section === 'badge_remove') {
            $programRepo->deactivateBadge((int)($_POST['id'] ?? 0));
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Badge removida (desativada) com sucesso.</div>';
            return;
        }
        if ($section === 'mission') {
            $eventKey = trim((string)($_POST['event_key'] ?? ''));
            $allowedMissionKeys = array_keys($this->missionEventOptions());
            if (!in_array($eventKey, $allowedMissionKeys, true)) {
                $_SESSION['error'] = 'Evento da missão inválido. Selecione uma opção permitida.';
                return;
            }
            $programRepo->updateMission((int)($_POST['id'] ?? 0), [
                'title' => (string)($_POST['title'] ?? ''),
                'description' => (string)($_POST['description'] ?? ''),
                'event_key' => $eventKey,
                'target_value' => (int)($_POST['target_value'] ?? 1),
                'reward_points' => (int)($_POST['reward_points'] ?? 0),
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'is_active' => (int)($_POST['is_active'] ?? 0) === 1,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Missão atualizada com sucesso.</div>';
            return;
        }
        if ($section === 'mission_create') {
            $title = trim((string)($_POST['title'] ?? ''));
            $eventKey = trim((string)($_POST['event_key'] ?? 'timeline_comment_created'));
            $allowedMissionKeys = array_keys($this->missionEventOptions());
            if ($title === '') {
                $_SESSION['error'] = 'Informe o título da missão.';
                return;
            }
            if (!in_array($eventKey, $allowedMissionKeys, true)) {
                $_SESSION['error'] = 'Evento da missão inválido. Selecione uma opção permitida.';
                return;
            }
            $programRepo->createMission([
                'title' => $title,
                'description' => (string)($_POST['description'] ?? ''),
                'event_key' => $eventKey,
                'target_value' => (int)($_POST['target_value'] ?? 1),
                'reward_points' => (int)($_POST['reward_points'] ?? 0),
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'is_active' => true,
            ]);
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Missão criada com sucesso.</div>';
            return;
        }
        if ($section === 'mission_remove') {
            $programRepo->deactivateMission((int)($_POST['id'] ?? 0));
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Missão removida (desativada) com sucesso.</div>';
            return;
        }
        if ($section === 'setting') {
            $key = (string)($_POST['setting_key'] ?? '');
            $programRepo->updateSetting($key, (string)($_POST['setting_value'] ?? ''));
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Configuração anti-fraude atualizada com sucesso.</div>';
        }
    }

    /** Fragmento #tab-* para manter a aba correta após POST (Bootstrap tabs). */
    private static function redirectHashForGamificationSection(string $section): string
    {
        if (in_array($section, ['level', 'level_create', 'level_remove'], true)) {
            return '#tab-levels';
        }
        if (in_array($section, ['badge', 'badge_create', 'badge_remove'], true)) {
            return '#tab-badges';
        }
        if (in_array($section, ['mission', 'mission_create', 'mission_remove'], true)) {
            return '#tab-missions';
        }
        if ($section === 'setting') {
            return '#tab-antifraud';
        }

        return '';
    }
}
