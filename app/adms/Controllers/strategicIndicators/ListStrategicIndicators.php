<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;

class ListStrategicIndicators
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new StrategicIndicatorsRepository($pdo);
    }

    public function index(): void
    {
        $indicators = $this->repository->getAll();
        include_once __DIR__ . '/../../../Views/layouts/header.php';
        include __DIR__ . '/../../../Views/strategicIndicators/list.php';
        include_once __DIR__ . '/../../../Views/layouts/footer.php';
    }
} 