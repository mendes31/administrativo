<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\TalentNominationsRepository;
use App\adms\Models\Services\TalentNominationService;
use App\adms\Views\Services\LoadViewService;

class UpdateTalentNomination
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $nid = (int) $id;
        if ($nid <= 0) {
            $_SESSION['error'] = 'Nomeação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-talent-nominations');
            exit;
        }

        $repository = new TalentNominationsRepository();
        $nomination = $repository->getById($nid);
        if (!$nomination) {
            $_SESSION['error'] = 'Nomeação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-talent-nominations');
            exit;
        }

        $this->data['nomination'] = $nomination;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($nid);
            $this->data['nomination'] = $repository->getById($nid) ?? $nomination;
        }

        $pageElements = [
            'title_head' => 'Editar Nomeação HiPo',
            'menu' => 'list-talent-nominations',
            'buttonPermission' => ['ListTalentNominations', 'ViewTalentNomination'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/update_talent_nomination', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_talent_nomination', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $result = (new TalentNominationService())->update($id, $_POST);
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Nomeação atualizada!</div>';
        GenerateLog::generateLog('info', 'Talent nomination atualizada.', ['id' => $id]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-talent-nomination/' . $id);
        exit;
    }
}
