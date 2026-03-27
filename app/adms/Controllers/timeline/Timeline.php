<?php

namespace App\adms\Controllers\timeline;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TimelineMentionHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class Timeline
{
    private array $data = [];

    public function index(string|int $page = 1): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && isset($_GET['mark_notification']) && is_numeric($_GET['mark_notification'])) {
            $notifRepo = new NotificationsRepository();
            $notifRepo->markAsRead((int)$_GET['mark_notification'], $userId);
        }

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        $page = max(1, (int)$page);
        $perPage = 10;

        $repo = new TimelineRepository();
        $this->data['posts'] = $repo->getFeedPosts($page, $perPage);

        // Anexa mídias (múltiplas fotos) aos posts.
        $postIdsForImages = array_map(static fn ($p) => (int)($p['id'] ?? 0), $this->data['posts']);
        $imagesMap = $repo->getPostImagesByPostIds($postIdsForImages);
        foreach ($this->data['posts'] as &$p) {
            $pid = (int)($p['id'] ?? 0);
            $hasVideo = !empty($p['video_path']);
            if ($hasVideo) {
                $p['image_paths'] = [];
                continue;
            }

            $paths = $imagesMap[$pid] ?? [];
            // Compatibilidade: posts antigos ainda usam adms_timeline_posts.image_path (1 foto).
            if ($paths === [] && !empty($p['image_path'])) {
                $paths = [(string)$p['image_path']];
            }
            $p['image_paths'] = $paths;
        }
        unset($p);

        $userRepo = new UsersRepository();
        $mentionIds = [];
        foreach ($this->data['posts'] as $p) {
            $mentionIds = array_merge(
                $mentionIds,
                TimelineMentionHelper::extractMentionedUserIds((string)($p['content'] ?? ''), $userRepo, null, false)
            );
        }
        $mentionIds = array_values(array_unique($mentionIds));
        $this->data['mention_name_map'] = $userRepo->getIdNameMapForIds($mentionIds);

        $postIds = array_map(static fn ($p) => (int)($p['id'] ?? 0), $this->data['posts']);
        $this->data['current_user_id'] = $userId;
        $this->data['reaction_map'] = $repo->getUserReactionMap($userId, $postIds);
        $this->data['reaction_summaries'] = $repo->getReactionSummariesByPostIds($postIds);
        $this->data['csrf_timeline_edit'] = CSRFHelper::generateCSRFToken('timeline_edit_post');
        $this->data['csrf_timeline_comment'] = CSRFHelper::generateCSRFToken('timeline_comment_post');
        $this->data['csrf_timeline_like'] = CSRFHelper::generateCSRFToken('timeline_like_post');
        $this->data['csrf_timeline_report'] = CSRFHelper::generateCSRFToken('timeline_report_post');

        $total = $repo->countActivePosts();
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'timeline',
            []
        );

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission([
            'CreateTimelinePost',
            'TimelineModerate',
            'TimelineReport',
        ]);
        $this->data['can_create'] = is_array($perms) && in_array('CreateTimelinePost', $perms, true);
        $this->data['can_moderate'] = is_array($perms) && in_array('TimelineModerate', $perms, true);
        $this->data['can_report'] = is_array($perms) && in_array('TimelineReport', $perms, true);

        $pageElements = [
            'title_head' => 'Timeline',
            'menu' => 'timeline',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/timeline/feed', $this->data);
        $loadView->loadView();
    }
}
