<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdRopaRepository;

class LgpdRopaDelete
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!isset($this->data['form']['csrf_token']) || !CSRFHelper::validateCSRFToken('form_delete_ropa', $this->data['form']['csrf_token']) || !isset($this->data['form']['id'])) {
            $_SESSION['error'] = "Registro não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
            return;
        }
        $repo = new LgpdRopaRepository();
        $registro = $repo->getById((int) $this->data['form']['id']);
        if (!$registro) {
            $_SESSION['error'] = "Registro não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
            return;
        }
        $result = $repo->delete($this->data['form']['id']);
        if ($result) {
            $_SESSION['success'] = "Registro apagado com sucesso!";
        } else {
            $_SESSION['error'] = "Registro não apagado!";
        }
        header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
        return;
    }
} 