<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaMovimentacoesRepository;
use App\adms\Models\Repository\PortariaPontosRepository;
use App\adms\Models\Repository\PortariaTermoAceitesRepository;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaMovimentacoes
{
    private const TERMO_TIPO = 'acesso_dependencias';

    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        $this->data['busca'] = trim((string) ($_GET['busca'] ?? ($this->data['form']['busca'] ?? '')));

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_portaria_movimentacao', (string) $this->data['form']['csrf_token'])) {
            $this->processar();
        }

        $this->carregarContexto();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Movimentações da Portaria',
            'menu' => 'portaria-movimentacoes',
            'buttonPermission' => ['PortariaPainel', 'PortariaAutorizacoesView'],
        ]));
        (new LoadViewService('adms/Views/portaria/movimentacoes/form', $this->data))->loadView();
    }

    private function carregarContexto(): void
    {
        $form = $this->data['form'] ?? [];
        $busca = (string) ($this->data['busca'] ?? '');
        $visitanteId = (int) ($form['visitante_id'] ?? $_GET['visitante_id'] ?? 0);
        $autorizacaoId = (int) ($form['autorizacao_id'] ?? $_GET['autorizacao_id'] ?? 0);

        if ($visitanteId > 0 && empty($form['visitante_id'])) {
            $this->data['form']['visitante_id'] = $visitanteId;
        }
        if ($autorizacaoId > 0 && empty($form['autorizacao_id'])) {
            $this->data['form']['autorizacao_id'] = $autorizacaoId;
        }
        if (!empty($_GET['ponto_controle_id']) && empty($form['ponto_controle_id'])) {
            $this->data['form']['ponto_controle_id'] = (int) $_GET['ponto_controle_id'];
        }

        $this->data['pontos'] = (new PortariaPontosRepository())->listAtivos();
        $this->data['presentes'] = (new PortariaMovimentacoesRepository())->listPresentesAgora();
        $this->data['termo_ativo'] = (new LgpdTermosRepository())->getTermoAtivoPorTipo(self::TERMO_TIPO);

        $this->data['resultados_busca'] = [];
        $this->data['visitantes_busca'] = [];
        if ($busca !== '') {
            $this->data['resultados_busca'] = (new PortariaAutorizacoesRepository())->buscarPorVisitante($busca, true);
            $this->data['visitantes_busca'] = (new PortariaVisitantesRepository())->getAll([
                'busca' => $busca,
                'ativo' => 1,
            ]);
        }

        $visitanteId = (int) ($this->data['form']['visitante_id'] ?? 0);
        $this->data['visitante_selecionado'] = null;
        $this->data['termo_vigente'] = null;
        if ($visitanteId > 0) {
            $this->data['visitante_selecionado'] = (new PortariaVisitantesRepository())->getById($visitanteId);
            $this->data['termo_vigente'] = (new PortariaTermoAceitesRepository())->getVigente($visitanteId);
            $this->data['autorizacoes_visitante'] = (new PortariaAutorizacoesRepository())->getAll([
                'visitante_id' => $visitanteId,
                'somente_vigentes_hoje' => true,
            ]);
        }

        // Sem aceite vigente: exibir assinatura antes da entrada.
        if ($visitanteId > 0 && $this->data['termo_vigente'] === null) {
            $this->data['termo_pendente'] = true;
            if (empty($this->data['warning'])) {
                $this->data['warning'] = 'Antes da entrada, solicite a assinatura do termo de acesso (não há aceite vigente).';
            }
        }
    }

    private function processar(): void
    {
        $form = $this->data['form'];
        $visitanteId = (int) ($form['visitante_id'] ?? 0);
        $pontoId = (int) ($form['ponto_controle_id'] ?? 0);
        $acao = (string) ($form['acao'] ?? '');

        if ($acao === 'selecionar') {
            // Apenas pré-seleciona visitante/autorização no formulário.
            return;
        }

        if ($visitanteId <= 0 || $pontoId <= 0) {
            $this->data['errors'] = ['Informe o visitante (pela busca) e o ponto de controle.'];
            return;
        }

        $form['porteiro_user_id'] = (int) ($_SESSION['user_id'] ?? 0);
        $repo = new PortariaMovimentacoesRepository();

        if ($acao === 'formalizar_termo' || $acao === 'formalizar_termo_e_entrar') {
            if (!$this->formalizarTermo($visitanteId, $form)) {
                $this->data['exigir_termo'] = true;
                return;
            }
            if ($acao === 'formalizar_termo') {
                $_SESSION['msg'] = 'Termo assinado. Agora registre a entrada.';
                $_SESSION['msg_type'] = 'success';
                $this->redirectComContexto($form);
                return;
            }
            // Continua para registrar entrada abaixo.
            $acao = 'entrada';
            $form['acao'] = 'entrada';
        }

        if ($acao === 'entrada' || $acao === 'regularizar') {
            if (!$this->termoEstaVigente($visitanteId)) {
                $this->data['termo_pendente'] = true;
                $this->data['exigir_termo'] = true;
                $this->data['termo_ativo'] = (new LgpdTermosRepository())->getTermoAtivoPorTipo(self::TERMO_TIPO);
                $this->data['warning'] = 'Antes da entrada, o visitante precisa assinar o termo de acesso (aceite vigente).';
                return;
            }
        }

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

    private function termoEstaVigente(int $visitanteId): bool
    {
        return (new PortariaTermoAceitesRepository())->getVigente($visitanteId) !== null;
    }

    /** @param array<string, mixed> $form */
    private function formalizarTermo(int $visitanteId, array $form): bool
    {
        $termo = (new LgpdTermosRepository())->getTermoAtivoPorTipo(self::TERMO_TIPO);
        if ($termo === null) {
            $this->data['errors'] = [
                'Não há termo LGPD ativo do tipo "Acesso às Dependências (Portaria)". '
                . 'Cadastre em LGPD → Termos antes de formalizar.',
            ];
            $this->data['termo_pendente'] = true;
            $this->data['exigir_termo'] = true;
            return false;
        }
        if (empty($form['aceite_termo']) || empty($form['conferencia_identidade'])) {
            $this->data['errors'] = [
                'Para assinar: marque que o visitante leu/aceitou o termo e confirme a conferência da identidade.',
            ];
            $this->data['termo_pendente'] = true;
            $this->data['exigir_termo'] = true;
            $this->data['termo_ativo'] = $termo;
            return false;
        }

        $meses = (int) ($form['validade_meses'] ?? 12);
        if ($meses < 1) {
            $meses = 12;
        }
        if ($meses > 60) {
            $meses = 60;
        }

        $aceiteId = (new PortariaTermoAceitesRepository())->create([
            'visitante_id' => $visitanteId,
            'lgpd_termo_id' => (int) $termo['id'],
            'metodo' => 'presencial',
            'porteiro_user_id' => (int) ($form['porteiro_user_id'] ?? $_SESSION['user_id'] ?? 0),
            'conferencia_identidade' => 1,
            'aceito_em' => date('Y-m-d H:i:s'),
            'valido_ate' => date('Y-m-d H:i:s', strtotime('+' . $meses . ' months')),
            'dispositivo' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'observacoes' => trim((string) ($form['termo_observacoes'] ?? '')),
        ]);

        if (!$aceiteId) {
            $this->data['errors'] = ['Não foi possível registrar o aceite do termo.'];
            $this->data['termo_pendente'] = true;
            $this->data['exigir_termo'] = true;
            $this->data['termo_ativo'] = $termo;
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $form */
    private function redirectComContexto(array $form): void
    {
        $qs = http_build_query([
            'busca' => (string) ($form['busca'] ?? $this->data['busca'] ?? ''),
            'visitante_id' => (int) ($form['visitante_id'] ?? 0),
            'autorizacao_id' => (int) ($form['autorizacao_id'] ?? 0),
            'ponto_controle_id' => (int) ($form['ponto_controle_id'] ?? 0),
        ]);
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-movimentacoes?' . $qs);
    }

    private function redirectSuccess(string $message): void
    {
        $_SESSION['msg'] = $message;
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'portaria-movimentacoes');
    }
}
