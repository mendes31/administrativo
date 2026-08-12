<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\BranchesRepository;
use App\adms\Models\Repository\PortariaPontosRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaPontosUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $pontoId = (int) $id;
        $repo = new PortariaPontosRepository();
        $ponto = $repo->getById($pontoId);
        if ($ponto === null) {
            $_SESSION['msg'] = 'Ponto de controle não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-pontos');
            return;
        }
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $this->data['form'] = is_array($post) ? $post : $ponto;
        if (is_array($post) && isset($post['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_ponto_' . $pontoId, (string) $post['csrf_token'])) {
            $this->data['form']['ativo'] = !empty($post['ativo']) ? 1 : 0;
            if (trim((string) ($post['nome'] ?? '')) === '') {
                $this->data['errors'] = ['Informe o nome do ponto de controle.'];
            } elseif ($repo->update($pontoId, $this->data['form'])) {
                $_SESSION['msg'] = 'Ponto de controle atualizado.';
                $_SESSION['msg_type'] = 'success';
                header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-pontos');
                return;
            } else {
                $this->data['errors'] = ['Não foi possível atualizar o ponto.'];
            }
        }
        $this->data['ponto_id'] = $pontoId;
        $this->data['filiais'] = (new BranchesRepository())->getAllBranchesSelect();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Editar Ponto de Controle',
            'menu' => 'portaria-pontos',
            'buttonPermission' => ['PortariaPontos'],
        ]));
        (new LoadViewService('adms/Views/portaria/pontos/edit', $this->data))->loadView();
    }
}
