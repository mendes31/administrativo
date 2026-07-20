<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CareerLevelsRepository;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\CareerService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewCareerTrack
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $tid = (int) $id;
        $repo = new CareerTracksRepository();
        $track = $tid > 0 ? $repo->getById($tid) : null;
        if (!$track) {
            $_SESSION['error'] = 'Trilha não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-career-tracks');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($tid);
            $track = $repo->getById($tid) ?? $track;
        }
        $this->data['track'] = $track;
        $this->data['levels'] = (new CareerLevelsRepository())->getByTrackId($tid);
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_career_tracks', $tid, $_ENV['URL_ADM'] . 'view-career-track/' . $tid);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Trilha — ' . ($track['name'] ?? ''),
            'menu' => 'list-career-tracks',
            'buttonPermission' => ['ListCareerTracks', 'UpdateCareerTrack', 'ListCareerPromotions'],
        ]));
        (new LoadViewService('adms/Views/performance/view_career_track', $this->data))->loadView();
    }

    private function handlePost(int $tid): void
    {
        $action = (string) ($_POST['form_action'] ?? '');
        $map = [
            'add_level' => 'form_career_add_level',
            'update_level' => 'form_career_update_level',
            'remove_level' => 'form_career_remove_level',
        ];
        if (!isset($map[$action]) || !CSRFHelper::validateCSRFToken($map[$action], $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Ação ou token inválido.';
            return;
        }
        $service = new CareerService();
        $result = match ($action) {
            'add_level' => $service->addLevel($tid, $_POST),
            'update_level' => $service->updateLevel((int) ($_POST['level_id'] ?? 0), $tid, $_POST),
            'remove_level' => $service->removeLevel((int) ($_POST['level_id'] ?? 0), $tid),
            default => ['ok' => false, 'error' => 'Ação inválida.'],
        };
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro.';
            return;
        }
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">OK</div>';
        GenerateLog::generateLog('info', 'Career level: ' . $action, ['track_id' => $tid]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-career-track/' . $tid);
        exit;
    }
}
