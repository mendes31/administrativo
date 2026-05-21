<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TimelineFeedEnricher;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Perfil de colaborador na timeline: bio e publicações do usuário.
 */
class TimelineProfile
{
    private array $data = [];

    public function index(string|int|null $profileUserId = null): void
    {
        $viewerId = (int)($_SESSION['user_id'] ?? 0);
        $uid = is_numeric($profileUserId) ? (int)$profileUserId : 0;
        if ($uid <= 0) {
            $_SESSION['error'] = 'Perfil inválido.';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'timeline');
            return;
        }

        $userRepo = new UsersRepository();
        $profile = $userRepo->getUserForTimelineProfile($uid);
        if (!$profile || (string)($profile['status'] ?? '') !== 'Ativo') {
            $_SESSION['error'] = 'Usuário não encontrado ou indisponível.';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'timeline');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $viewerId === $uid) {
            $token = (string)($_POST['csrf_token'] ?? '');
            if (CSRFHelper::validateCSRFToken('form_timeline_profile_bio', $token)) {
                $bio = (string)($_POST['timeline_bio'] ?? '');
                if ($userRepo->updateTimelineBio($viewerId, $bio)) {
                    $_SESSION['success'] = 'Apresentação atualizada.';
                } else {
                    $_SESSION['error'] = 'Não foi possível salvar a apresentação.';
                }
            } else {
                $_SESSION['error'] = 'Token de segurança inválido.';
            }
            header('Location: ' . rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/timeline-profile/' . $uid);
            return;
        }

        // Recarrega após possível redirect
        $profile = $userRepo->getUserForTimelineProfile($uid);
        if (!$profile) {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'timeline');
            return;
        }

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        } else {
            $page = 1;
        }
        $page = max(1, $page);
        $perPage = 10;

        $repo = new TimelineRepository();
        $this->data['posts'] = $repo->getFeedPostsByUserId($uid, $page, $perPage);

        $enriched = TimelineFeedEnricher::enrich($repo, $userRepo, $this->data['posts'], $viewerId);
        $this->data['posts'] = $enriched['posts'];
        $this->data['mention_name_map'] = $enriched['mention_name_map'];
        $this->data['reaction_map'] = $enriched['reaction_map'];
        $this->data['reaction_summaries'] = $enriched['reaction_summaries'];
        $this->data['poll_map'] = $enriched['poll_map'];
        $this->data['current_user_id'] = $viewerId;

        $this->data['timeline_profile_user_id'] = $uid;
        $this->data['timeline_profile'] = $profile;
        $this->data['timeline_profile_is_own'] = $viewerId > 0 && $viewerId === $uid;
        $this->data['resolved_focus_post_id'] = 0;
        $this->data['resolved_focus_comment_id'] = 0;
        $this->data['active_tag'] = '';
        $this->data['search_query'] = '';
        // Texto sugerido para o composer quando acessado a partir de widgets (aniversário/tempo de empresa)
        $prefillSource = isset($_GET['from']) ? (string)$_GET['from'] : '';
        $prefillYears = isset($_GET['years']) && is_numeric($_GET['years'])
            ? max(0, (int)$_GET['years'])
            : null;
        $composerPrefill = '';
        if ($prefillSource !== '' && !$this->data['timeline_profile_is_own']) {
            $username = (string)($profile['username'] ?? '');
            if ($username !== '') {
                if ($prefillSource === 'birthday') {
                    $composerPrefill = 'Feliz aniversário, @' . $username . '! ';
                } elseif ($prefillSource === 'tenure') {
                    if ($prefillYears === 0) {
                        $composerPrefill = 'Bem-vindo(a) à empresa, @' . $username . '! Sucesso nessa nova etapa! ';
                    } elseif ($prefillYears !== null) {
                        $composerPrefill = 'Parabéns pelos seus ' . $prefillYears . ' ano(s) de empresa, @' . $username . '! ';
                    } else {
                        $composerPrefill = 'Parabéns pelo seu tempo de empresa, @' . $username . '! ';
                    }
                }
            }
        }
        $this->data['timeline_composer_prefill'] = $composerPrefill;
        $this->data['timeline_composer_context'] = [
            'type' => $prefillSource,
            'target_user_id' => $uid,
            'years' => $prefillYears,
        ];
        $this->data['csrf_timeline_profile_bio'] = CSRFHelper::generateCSRFToken('form_timeline_profile_bio');
        $this->data['csrf_timeline_edit'] = CSRFHelper::generateCSRFToken('timeline_edit_post');
        $this->data['csrf_timeline_comment'] = CSRFHelper::generateCSRFToken('timeline_comment_post');
        $this->data['csrf_timeline_like'] = CSRFHelper::generateCSRFToken('timeline_like_post');
        $this->data['csrf_timeline_report'] = CSRFHelper::generateCSRFToken('timeline_report_post');

        $total = $repo->countActivePostsByUserId($uid);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $perPage,
            $page,
            'timeline-profile/' . $uid,
            []
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
            'TimelineFeaturePost',
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
        $this->data['can_feature'] = is_array($perms) && in_array('TimelineFeaturePost', $perms, true);

        $displayName = (string)($profile['name'] ?? 'Perfil');
        $pageElements = [
            'title_head' => $displayName . ' — Timeline',
            'menu' => 'timeline',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/timeline/feed', $this->data);
        $loadView->loadView();
    }
}
