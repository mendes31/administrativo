<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;

class CreateStrategicPlan
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new StrategicPlansRepository($pdo);
    }

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $this->repository->create($data);
            header('Location: /adms/strategicPlans/list');
            exit;
        }
        // Inclui cabeçalho padrão
        include_once __DIR__ . '/../../../Views/layouts/header.php';
        // Renderiza a view de cadastro
        include __DIR__ . '/../../../Views/strategicPlans/create.php';
        // Inclui rodapé padrão
        include_once __DIR__ . '/../../../Views/layouts/footer.php';
    }
} 