<?php

namespace App\adms\Controllers\inventory;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\InvEnergyClassFactorService;

class SaveInvEnergyClassFactors
{
    public function index(): void
    {
        $redirect = $_ENV['URL_ADM'] . 'list-inventory-energy-class-factors';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_save_inv_energy_class_factors', $token, false)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Sessão expirada ou token inválido. Recarregue a página e tente novamente.</div>";
            header('Location: ' . $redirect);
            exit;
        }

        $factors = $_POST['factors'] ?? [];
        if (!is_array($factors)) {
            $factors = [];
        }

        InvEnergyClassFactorService::saveFromForm($factors);
        CSRFHelper::validateCSRFToken('form_save_inv_energy_class_factors', $token, true);

        $_SESSION['msg'] = "<div class='alert alert-success'>Multiplicadores HVAC salvos. Recalcule os períodos de custeio em aberto para aplicar o critério 8.</div>";
        header('Location: ' . $redirect);
        exit;
    }
}
