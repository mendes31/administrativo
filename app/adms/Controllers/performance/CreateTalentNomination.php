<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\TalentNominationService;
use App\adms\Views\Services\LoadViewService;

class CreateTalentNomination
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $this->data['employees'] = (new UsersRepository())->getAllUsers(1, 1000);
        $this->data['cycles'] = (new PerformanceCyclesRepository())->getAll([], 1, 200);
        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'user_id' => $_GET['user_id'] ?? '',
                'performance_cycle_id' => $_GET['performance_cycle_id'] ?? '',
                'nine_box' => $_GET['nine_box'] ?? '',
                'notes' => '',
            ];
        }

        $pageElements = [
            'title_head' => 'Nomear no Talent Pool',
            'menu' => 'list-talent-nominations',
            'buttonPermission' => ['ListTalentNominations'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/create_talent_nomination', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_talent_nomination', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-talent-nomination');
            exit;
        }

        $result = (new TalentNominationService())->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao nomear.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Nomeação registrada no talent pool!</div>';
        GenerateLog::generateLog('info', 'Talent nomination criada.', ['id' => $result['id']]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-talent-nomination/' . $result['id']);
        exit;
    }
}
