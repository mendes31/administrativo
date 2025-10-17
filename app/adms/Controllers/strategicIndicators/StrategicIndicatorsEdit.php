<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;

class StrategicIndicatorsEdit
{
    private $repository;

    public function __construct()
    {
        $this->repository = new StrategicIndicatorsRepository();
    }

    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do indicador não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "strategic-indicators-list");
            exit;
        }

        $indicator = $this->repository->getById($id);
        if (!$indicator) {
            header('Location: /adms/strategicIndicators/list');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $this->repository->update($id, $data);
            header('Location: /adms/strategicIndicators/list');
            exit;
        }
        include_once __DIR__ . '/../../../Views/layouts/header.php';
        include __DIR__ . '/../../../Views/strategicIndicators/edit.php';
        include_once __DIR__ . '/../../../Views/layouts/footer.php';
    }
} 