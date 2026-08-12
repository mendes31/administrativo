<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaAutorizacoesView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $autorizacaoId = (int) $id;
        $repo = new PortariaAutorizacoesRepository();
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (is_array($post) && isset($post['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_autorizacao_view_' . $autorizacaoId, (string) $post['csrf_token'])) {
            $actorId = (int) ($_SESSION['user_id'] ?? 0);
            $acao = (string) ($post['acao'] ?? '');
            $ok = match ($acao) {
                'autorizar' => $repo->updateStatus($autorizacaoId, 'autorizada', $actorId),
                'recusar' => $repo->updateStatus($autorizacaoId, 'recusada', $actorId),
                'contato' => $repo->registrarContato($autorizacaoId, $post, $actorId),
                default => false,
            };
            $_SESSION['msg'] = $ok ? 'Operação registrada com sucesso.' : 'Não foi possível registrar a operação.';
            $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-autorizacoes-view/' . $autorizacaoId);
            return;
        }
        $autorizacao = $repo->getById($autorizacaoId);
        if ($autorizacao === null) {
            $_SESSION['msg'] = 'Autorização não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-autorizacoes');
            return;
        }
        $this->data['autorizacao'] = $autorizacao;
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Detalhe da Autorização',
            'menu' => 'portaria-autorizacoes',
            'buttonPermission' => ['PortariaAutorizacoes', 'PortariaMovimentacoes'],
        ]));
        (new LoadViewService('adms/Views/portaria/autorizacoes/view', $this->data))->loadView();
    }
}
