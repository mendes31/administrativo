<?php

namespace App\adms\Controllers\timeline;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FlashMessageHelper;
use App\adms\Views\Services\LoadViewService;

class TimelineModerate
{
    private array $data = [];

    public function index(string|int|null $routeParam = null): void
    {
        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission(['TimelineModerate']);
        if (!is_array($perms) || !in_array('TimelineModerate', $perms, true)) {
            FlashMessageHelper::push('Sem permissão para moderação.', 'danger', 'timeline');
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline');
            exit;
        }

        $repo = new TimelineRepository();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!CSRFHelper::validateCSRFToken('timeline_moderate', $_POST['csrf_token'] ?? '')) {
                FlashMessageHelper::push('Token CSRF inválido.', 'danger', 'timeline-moderate');
            } else {
                $action = $_POST['action'] ?? '';
                $reportId = (int)($_POST['report_id'] ?? 0);
                $postId = (int)($_POST['post_id'] ?? 0);
                $modId = (int)$_SESSION['user_id'];
                if ($action === 'hide_post' && $postId > 0) {
                    $repo->hidePost($postId, $modId, (string)($_POST['reason'] ?? 'Ocultado por moderação'));
                    if ($reportId > 0) {
                        $repo->markReportReviewed($reportId, $modId);
                    }
                    FlashMessageHelper::push('Post ocultado.', 'success', 'timeline-moderate');
                } elseif ($action === 'dismiss' && $reportId > 0) {
                    $repo->markReportReviewed($reportId, $modId);
                    FlashMessageHelper::push('Denúncia arquivada.', 'success', 'timeline-moderate');
                }
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'timeline-moderate');
            exit;
        }

        $this->data['reports'] = $repo->listOpenReports(200);

        $pageElements = [
            'title_head' => 'Moderação da Timeline',
            'menu' => 'timeline-moderate',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/timeline/moderate', $this->data);
        $loadView->loadView();
    }
}
