<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\CareerService;
use App\adms\Views\Services\LoadViewService;

class CreateCareerTrack
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_create_career_track', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new CareerService())->createTrack($_POST, (int) ($_SESSION['user_id'] ?? 0));
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Trilha criada!</div>';
                    GenerateLog::generateLog('info', 'Trilha criada.', ['id' => $result['id']]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-career-track/' . $result['id']);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro.';
                $this->data['form'] = $_POST;
            }
        }
        $this->data['form'] ??= ['name' => '', 'description' => '', 'status' => 'active'];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Criar Trilha de Carreira',
            'menu' => 'list-career-tracks',
            'buttonPermission' => ['ListCareerTracks'],
        ]));
        (new LoadViewService('adms/Views/performance/create_career_track', $this->data))->loadView();
    }
}
