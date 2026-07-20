<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CareerTracksRepository;
use App\adms\Models\Services\CareerService;
use App\adms\Views\Services\LoadViewService;

class UpdateCareerTrack
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
        $this->data['track'] = $track;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_update_career_track', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new CareerService())->updateTrack($tid, $_POST);
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Trilha atualizada!</div>';
                    GenerateLog::generateLog('info', 'Trilha atualizada.', ['id' => $tid]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-career-track/' . $tid);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro.';
            }
            $this->data['track'] = $repo->getById($tid) ?? $track;
        }
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Editar Trilha',
            'menu' => 'list-career-tracks',
            'buttonPermission' => ['ListCareerTracks', 'ViewCareerTrack'],
        ]));
        (new LoadViewService('adms/Views/performance/update_career_track', $this->data))->loadView();
    }
}
