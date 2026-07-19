<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhMovimentacoesRepository;
use App\adms\Views\Services\LoadViewService;

final class RhMovimentacoesView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $movId = (int) $id;
        $mov = (new RhMovimentacoesRepository())->getById($movId);
        if ($mov === null) {
            $_SESSION['msg'] = 'Movimentação não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes');
            exit;
        }

        $this->data = [
            'title_head' => 'Movimentação #' . $movId,
            'menu' => 'rh-movimentacoes-view',
            'buttonPermission' => ['RhMovimentacoesView', 'RhMovimentacoes'],
            'movimentacao' => $mov,
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/movimentacoes/view', $this->data))->loadView();
    }
}
