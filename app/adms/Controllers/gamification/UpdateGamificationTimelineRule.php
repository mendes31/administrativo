<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateGamificationTimelineRule
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Regra inválida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-timeline-rules');
            exit;
        }

        $repo = new GamificationTimelineRulesRepository();
        $rule = $repo->findById($id);
        if (!$rule) {
            $_SESSION['error'] = 'Regra não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-timeline-rules');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->handlePost($id, $repo);
            $rule = $repo->findById($id) ?? $rule;
        }

        $this->data['rule'] = $rule;

        $pageElements = [
            'title_head' => 'Editar regra — Gamificação',
            'menu' => 'ListGamificationTimelineRules',
            'buttonPermission' => [
                'ListGamificationTimelineRules',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/update_timeline_rule', $this->data);
        $loadView->loadView();
    }

    private function handlePost(int $id, GamificationTimelineRulesRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_gamification_timeline_rule', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $maxDay = trim((string)($_POST['max_awards_per_user_per_day'] ?? ''));
        $maxTot = trim((string)($_POST['max_awards_per_user_total'] ?? ''));
        $data = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'points' => (int)($_POST['points'] ?? 0),
            'is_active' => isset($_POST['is_active']) && (string)$_POST['is_active'] === '1',
            'max_awards_per_user_per_day' => $maxDay === '' ? null : max(0, (int)$maxDay),
            'max_awards_per_user_total' => $maxTot === '' ? null : max(0, (int)$maxTot),
        ];
        if ($data['title'] === '') {
            $_SESSION['error'] = 'Informe o título da regra.';
            return;
        }

        $repo->updateById($id, $data);
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Regra atualizada com sucesso.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-gamification-timeline-rules');
        exit;
    }
}
