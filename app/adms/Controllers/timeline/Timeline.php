<?php

namespace App\adms\Controllers\timeline;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TimelineFeedEnricher;
use App\adms\Helpers\TimelineHashtagHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\TimelineCelebrationsService;
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

        // No primeiro acesso do dia à Timeline, gerar posts institucionais
        if ($userId > 0) {
            TimelineCelebrationsService::ensureTodayPostsCreated();
        }

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        $page = max(1, (int)$page);
        $perPage = 10;
        $activeTag = TimelineHashtagHelper::normalizeTag((string)($_GET['tag'] ?? ''));

        $searchQ = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($searchQ) > 200) {
            $searchQ = mb_substr($searchQ, 0, 200);
        }
        $searchForRepo = $searchQ !== '' ? $searchQ : null;

        $focusPostId = isset($_GET['post']) ? (int)$_GET['post'] : 0;
        $focusCommentId = isset($_GET['comment']) ? (int)$_GET['comment'] : 0;
        $resolvedFocusPostId = 0;
        $resolvedFocusCommentId = 0;

        $repo = new TimelineRepository();
        // comment= é fonte de verdade do post ao abrir notificação (evita post= errado na URL).
        if ($focusCommentId > 0) {
            $cRow = $repo->getCommentById($focusCommentId);
            if ($cRow && ($cRow['status'] ?? '') === 'active' && ($cRow['post_status'] ?? '') === 'active') {
                $resolvedFocusPostId = (int)($cRow['post_id'] ?? 0);
                $resolvedFocusCommentId = $focusCommentId;
                if ($resolvedFocusPostId > 0) {
                    $focusPostId = $resolvedFocusPostId;
                }
            }
        } elseif ($focusPostId > 0) {
            $resolvedFocusPostId = $focusPostId;
        }

        $this->data['resolved_focus_post_id'] = $resolvedFocusPostId;
        $this->data['resolved_focus_comment_id'] = $resolvedFocusCommentId;

        $this->data['posts'] = $repo->getFeedPosts($page, $perPage, $activeTag, $searchForRepo);
        $this->data['active_tag'] = $activeTag;
        $this->data['search_query'] = $searchQ;

        // Inclui publicação alvo (ex.: notificação) mesmo que esteja em outra página do feed.
        if ($focusPostId > 0) {
            $idsOnPage = array_map(static fn ($p) => (int)($p['id'] ?? 0), $this->data['posts']);
            if (!in_array($focusPostId, $idsOnPage, true)) {
                $extra = $repo->getPostsWithAuthorByIds([$focusPostId]);
                $row = $extra[$focusPostId] ?? null;
                if ($row && ($row['status'] ?? '') === 'active') {
                    $tagOk = $activeTag === '' || $repo->postHasNormalizedTag($focusPostId, $activeTag);
                    $searchOk = $searchForRepo === null
                        || mb_stripos((string)($row['content'] ?? ''), $searchForRepo, 0, 'UTF-8') !== false;
                    if ($tagOk && $searchOk) {
                        array_unshift($this->data['posts'], $row);
                    }
                }
            }
        }

        $userRepo = new UsersRepository();
        $enriched = TimelineFeedEnricher::enrich($repo, $userRepo, $this->data['posts'], $userId);
        $this->data['posts'] = $enriched['posts'];
        $this->data['mention_name_map'] = $enriched['mention_name_map'];
        $this->data['reaction_map'] = $enriched['reaction_map'];
        $this->data['reaction_summaries'] = $enriched['reaction_summaries'];
        $this->data['poll_map'] = $enriched['poll_map'];
        $this->data['current_user_id'] = $userId;
        $this->data['csrf_timeline_edit'] = CSRFHelper::generateCSRFToken('timeline_edit_post');
        $this->data['csrf_timeline_comment'] = CSRFHelper::generateCSRFToken('timeline_comment_post');
        $this->data['csrf_timeline_like'] = CSRFHelper::generateCSRFToken('timeline_like_post');
        $this->data['csrf_timeline_report'] = CSRFHelper::generateCSRFToken('timeline_report_post');

        $total = $repo->countActivePosts($activeTag, $searchForRepo);
        $pagFilters = [];
        if ($activeTag !== '') {
            $pagFilters['tag'] = $activeTag;
        }
        if ($searchQ !== '') {
            $pagFilters['q'] = $searchQ;
        }
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'timeline',
            $pagFilters
        );

        $permRepo = new ButtonPermissionUserRepository();
        $perms = $permRepo->buttonPermission([
            'CreateTimelinePost',
            'TimelineModerate',
            'TimelineReport',
            'TimelineComment',
            'TimelineViewComments',
            'TimelineLike',
            'TimelinePostReactions',
            'TimelineShare',
        ]);
        $this->data['can_create'] = is_array($perms) && in_array('CreateTimelinePost', $perms, true);
        $this->data['can_moderate'] = is_array($perms) && in_array('TimelineModerate', $perms, true);
        $this->data['can_report'] = is_array($perms) && in_array('TimelineReport', $perms, true);
        $this->data['can_comment'] = is_array($perms) && in_array('TimelineComment', $perms, true);
        $this->data['can_like'] = is_array($perms) && in_array('TimelineLike', $perms, true);
        $this->data['can_view_reactions'] = is_array($perms) && in_array('TimelinePostReactions', $perms, true);
        $this->data['can_view_comments'] = is_array($perms)
            && (in_array('TimelineComment', $perms, true) || in_array('TimelineViewComments', $perms, true));
        $this->data['can_share'] = is_array($perms) && in_array('TimelineShare', $perms, true);

        // Feed geral (não é página de perfil da timeline)
        $this->data['timeline_profile_user_id'] = 0;
        $this->data['timeline_profile'] = null;
        $this->data['timeline_profile_is_own'] = false;

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
