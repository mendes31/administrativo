<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaMovimentacoesRepository;
use App\adms\Models\Repository\PortariaPontosRepository;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaMovimentacoes
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_movimentacao', (string) $this->data['form']['csrf_token'])) {
            $this->processar();
        }
        $this->data['visitantes'] = (new PortariaVisitantesRepository())->getAll(['ativo' => 1]);
        $this->data['autorizacoes'] = (new PortariaAutorizacoesRepository())->getAll(['status' => 'autorizada']);
        $this->data['pontos'] = (new PortariaPontosRepository())->listAtivos();
        $this->data['presentes'] = (new PortariaMovimentacoesRepository())->listPresentesAgora();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Movimentações da Portaria',
            'menu' => 'portaria-movimentacoes',
            'buttonPermission' => ['PortariaPainel', 'PortariaAutorizacoesView'],
        ]));
        (new LoadViewService('adms/Views/portaria/movimentacoes/form', $this->data))->loadView();
    }

    private function processar(): void
    {
        $form = $this->data['form'];
        $visitanteId = (int) ($form['visitante_id'] ?? 0);
        $pontoId = (int) ($form['ponto_controle_id'] ?? 0);
        if ($visitanteId <= 0 || $pontoId <= 0) {
            $this->data['errors'] = ['Selecione o visitante e o ponto de controle.'];
            return;
        }
        $form['porteiro_user_id'] = (int) ($_SESSION['user_id'] ?? 0);
        $repo = new PortariaMovimentacoesRepository();
        $acao = (string) ($form['acao'] ?? '');
        if ($acao === 'entrada' && $repo->getEntradaAberta($visitanteId) !== null) {
            $this->data['entrada_aberta'] = $repo->getEntradaAberta($visitanteId);
            $this->data['warning'] = 'O visitante já está dentro. Regularize a saída anterior antes da nova entrada.';
            return;
        }
        if ($acao === 'regularizar') {
            if ($repo->regularizarSaidaEEntrar($form)) {
                $this->redirectSuccess('Saída faltante regularizada e nova entrada registrada.');
                return;
            }
            $this->data['errors'] = ['Informe o motivo da regularização.'];
            return;
        }
        $id = $acao === 'saida' ? $repo->registrarSaida($form) : $repo->registrarEntrada($form);
        if ($id) {
            $this->redirectSuccess($acao === 'saida' ? 'Saída registrada.' : 'Entrada registrada.');
            return;
        }
        $this->data['errors'] = ['Não foi possível registrar a movimentação.'];
    }

    private function redirectSuccess(string $message): void
    {
        $_SESSION['msg'] = $message;
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-movimentacoes');
    }
}
