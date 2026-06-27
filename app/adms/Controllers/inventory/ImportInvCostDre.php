<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostDreImportService;

class ImportInvCostDre
{
    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        $redirect = $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . max(1, $periodId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        if ($periodId <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_import_inv_cost_dre', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $repo = new InvCostPeriodsRepository();
        if ($repo->getOne($periodId) === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            exit;
        }

        if (empty($_FILES['dre_file']['tmp_name']) || !is_uploaded_file($_FILES['dre_file']['tmp_name'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Selecione um arquivo CSV para importar.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $replacePrevious = !empty($_POST['replace_previous']);
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $originalName = (string)($_FILES['dre_file']['name'] ?? 'dre.csv');

        $service = new InvCostDreImportService();
        $result = $service->importCsvForPeriod(
            $periodId,
            $_FILES['dre_file']['tmp_name'],
            $originalName,
            $replacePrevious,
            $userId,
            !isset($_POST['apply_suggested_criteria']) || !empty($_POST['apply_suggested_criteria'])
        );

        if ($result['success']) {
            CSRFHelper::validateCSRFToken('form_import_inv_cost_dre', $token, true);
        }

        $class = $result['success'] ? 'success' : 'danger';
        $_SESSION['msg'] = "<div class='alert alert-{$class}'>" . htmlspecialchars($result['message']) . '</div>';
        header('Location: ' . $redirect);
        exit;
    }
}
