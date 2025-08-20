<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;

class CreateStrategicIndicator
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new StrategicIndicatorsRepository($pdo);
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $this->repository->create($data);
            header('Location: /adms/strategicIndicators/list');
            exit;
        }
        include_once __DIR__ . '/../../../Views/layouts/header.php';
        include __DIR__ . '/../../../Views/strategicIndicators/create.php';
        include_once __DIR__ . '/../../../Views/layouts/footer.php';
    }
} 