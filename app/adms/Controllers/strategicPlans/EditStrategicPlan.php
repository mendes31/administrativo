<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;

class EditStrategicPlan
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new StrategicPlansRepository($pdo);
    }

    public function index(int $id): void
    {
        $plan = $this->repository->getById($id);
        if (!$plan) {
            header('Location: /adms/strategicPlans/list');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $this->repository->update($id, $data);
            header('Location: /adms/strategicPlans/list');
            exit;
        }
        // Inclui cabeçalho padrão
        include_once __DIR__ . '/../../../Views/layouts/header.php';
        // Renderiza a view de edição
        include __DIR__ . '/../../../Views/strategicPlans/edit.php';
        // Inclui rodapé padrão
        include_once __DIR__ . '/../../../Views/layouts/footer.php';
    }
} 