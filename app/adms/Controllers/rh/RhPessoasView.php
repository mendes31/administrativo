<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhIdentidadeRepository;
use App\adms\Views\Services\LoadViewService;

final class RhPessoasView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $pessoaId = (int) $id;
        $repo = new RhIdentidadeRepository();
        $pessoa = $repo->getPessoaById($pessoaId);
        if ($pessoa === null) {
            $_SESSION['msg'] = 'Pessoa não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-pessoas');
            exit;
        }

        $vinculo = null;
        $lotacao = null;
        if (!empty($pessoa['adms_user_id'])) {
            $vinculo = $repo->getVinculoAtualByUserId((int) $pessoa['adms_user_id']);
            if ($vinculo !== null) {
                $lotacao = $repo->getLotacaoVigente((int) $vinculo['id']);
            }
        }

        $this->data = [
            'title_head' => 'Pessoa #' . $pessoaId,
            'menu' => 'rh-pessoas-view',
            'buttonPermission' => ['RhPessoasView', 'RhPessoas'],
            'pessoa' => $pessoa,
            'vinculo' => $vinculo,
            'lotacao' => $lotacao,
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/pessoas/view', $this->data))->loadView();
    }
}
