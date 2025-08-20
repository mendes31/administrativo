<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;

class EditStrategicIndicator
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new StrategicIndicatorsRepository($pdo);
    }

    public function index(int $id): void
    {
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