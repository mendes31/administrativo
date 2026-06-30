<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\InvComplexityLevelFactorService;

class SaveInvComplexityLevelFactors
{
    public function index(): void
    {
        $redirect = $_ENV['URL_ADM'] . 'list-inventory-complexity-level-factors';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_save_inv_complexity_level_factors', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $factors = $_POST['factors'] ?? [];
        if (!is_array($factors)) {
            $factors = [];
        }

        InvComplexityLevelFactorService::saveFromForm($factors);
        CSRFHelper::validateCSRFToken('form_save_inv_complexity_level_factors', $token, true);

        $_SESSION['msg'] = "<div class='alert alert-success'>Fatores de complexidade salvos. Recalcule os períodos de custeio em aberto para aplicar os critérios 4 e 6.</div>";
        header('Location: ' . $redirect);
        exit;
    }
}
