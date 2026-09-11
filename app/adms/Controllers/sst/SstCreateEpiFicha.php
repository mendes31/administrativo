<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEpiMovimentoHelper;
use App\adms\Helpers\SstEpiPrevistaTrocaHelper;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEpiFichaPdfService;
use App\adms\Models\Services\SstEpiFichaPublishNotifier;
use App\adms\Models\Services\SstTreinamentoBloqueioService;
use App\adms\Views\Services\LoadViewService;

class SstCreateEpiFicha
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500);
        $estoqueSvc = new \App\adms\Models\Services\SstEpiEstoqueService();
        $this->data['cas_estoque_por_epi_json'] = json_encode(
            $estoqueSvc->metaEstoqueParaFormulario($this->data['epis']),
            JSON_UNESCAPED_UNICODE
        );
        $vidaUtilPorEpi = [];
        foreach ($this->data['epis'] as $ep) {
            $vidaUtilPorEpi[(int) ($ep['id'] ?? 0)] = (int) ($ep['periodicidade_troca_dias'] ?? 0);
        }
        $this->data['vida_util_por_epi_json'] = json_encode($vidaUtilPorEpi, JSON_UNESCAPED_UNICODE);
        $this->data['item'] = [];
        if (!empty($_GET['adms_user_id'])) {
            $this->data['item']['adms_user_id'] = (int) $_GET['adms_user_id'];
        }
        if (!empty($_GET['adms_sst_epi_id'])) {
            $this->data['item']['adms_sst_epi_id'] = (int) $_GET['adms_sst_epi_id'];
        }
        $this->data['item']['data_entrega'] = date('Y-m-d');
        $this->data['bloqueio_treinamento_ativo'] = SstTreinamentoBloqueioService::isAtivo();
        $this->data['pode_ignorar_bloqueio_treinamento'] = (new SstTreinamentoBloqueioService())->podeIgnorarBloqueio();
        $uidPreview = (int) ($this->data['item']['adms_user_id'] ?? 0);
        $this->data['impedimentos_treinamento_preview'] = $uidPreview > 0
            ? (new SstTreinamentoBloqueioService())->getImpedimentosEntregaEpi($uidPreview)
            : [];
        $pageElements = [
            'title_head' => 'Nova ficha de entrega EPI - SST',
            'menu' => 'sst-list-epi-fichas',
            'buttonPermission' => ['SstCreateEpiFicha'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_fichas/form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_epi_fichas_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Sessão expirada. Abra o formulário novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
        }

        $userId = (int) ($_POST['adms_user_id'] ?? 0);
        $dataEntrega = trim((string) ($_POST['data_entrega'] ?? ''));
        if ($userId <= 0 || $dataEntrega === '') {
            $_SESSION['msg'] = 'Informe colaborador e data da entrega.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
        }

        $itens = $this->parseItens($_POST, $dataEntrega);
        if ($itens === []) {
            $_SESSION['msg'] = 'Adicione ao menos um EPI com CA em estoque selecionado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
        }

        $bloqueioSvc = new SstTreinamentoBloqueioService();
        $bypass = !empty($_POST['confirmar_bypass_bloqueio_treinamento']);
        $avaliacao = $bloqueioSvc->avaliarEntregaEpi($userId, $bypass);
        if (!$avaliacao['permitido']) {
            $_SESSION['msg'] = $avaliacao['mensagem'] ?? 'Entrega bloqueada por treinamento SST.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha?adms_user_id=' . $userId);
            exit;
        }

        $movRepo = new SstEpiMovimentosRepository();
        $epiRepo = new SstEpisRepository();
        foreach ($itens as $item) {
            $epiId = (int) ($item['adms_sst_epi_id'] ?? 0);
            $ca = (string) ($item['ca_utilizado'] ?? '');
            $qty = (int) ($item['quantidade'] ?? 1);
            $tam = (string) ($item['tamanho'] ?? '');
            $epi = $epiRepo->getById($epiId);
            if ($epi && !empty($epi['controla_tamanho'])) {
                if ($tam === '') {
                    $_SESSION['msg'] = 'Informe o tamanho/numeração de cada EPI que controla grade.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
                    exit;
                }
                $saldoLote = $movRepo->getSaldoLote($epiId, $ca, $tam);
                if ($saldoLote < $qty) {
                    $_SESSION['msg'] = 'Saldo insuficiente para o CA ' . $ca . ' / tamanho ' . $tam . '. Disponível: ' . $saldoLote . '.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
                    exit;
                }
            } else {
                $saldoCa = $movRepo->getSaldoCa($epiId, $ca);
                if ($saldoCa < $qty) {
                    $_SESSION['msg'] = 'Saldo insuficiente para o CA ' . $ca . '. Disponível: ' . $saldoCa . '.';
                    $_SESSION['msg_type'] = 'danger';
                    header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
                    exit;
                }
            }
        }

        $repo = new SstEpiFichasRepository();
        try {
            $fichaId = $repo->createWithItens([
                'adms_user_id' => $userId,
                'data_entrega' => $dataEntrega,
                'status_assinatura' => 'Pendente',
                'observacoes' => trim((string) ($_POST['observacoes'] ?? '')),
            ], $itens);
        } catch (\Throwable) {
            $_SESSION['msg'] = 'Erro ao salvar ficha. Verifique se a migration de fichas EPI foi executada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
        }

        if (!$fichaId) {
            $_SESSION['msg'] = 'Erro ao salvar ficha.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
        }

        try {
            (new SstEpiFichaPdfService())->generateAndStore((int) $fichaId);
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Ficha criada, mas falha ao gerar PDF: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . $fichaId);
            exit;
        }

        $colaborador = (new UsersRepository())->getUser($userId);
        SstEpiFichaPublishNotifier::notifyPendingSignature(
            $userId,
            (int) $fichaId,
            (string) ($colaborador['name'] ?? 'Colaborador')
        );

        $_SESSION['msg'] = 'Ficha criada. O colaborador foi notificado para assinar no portal.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . $fichaId);
        exit;
    }

    /** @return list<array<string, mixed>> */
    private function parseItens(array $post, string $dataEntrega): array
    {
        $raw = $post['itens'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $epiId = (int) ($row['adms_sst_epi_id'] ?? 0);
            if ($epiId <= 0) {
                continue;
            }
            $ca = trim((string) ($row['ca_utilizado'] ?? ''));
            if ($ca === '') {
                continue;
            }
            $caNorm = SstEpiMovimentoHelper::normalizeCa($ca);
            $tam = \App\adms\Helpers\SstEpiTamanhoHelper::normalize((string) ($row['tamanho'] ?? ''));
            $prevInformada = trim((string) ($row['data_prevista_troca'] ?? ''));
            $prev = SstEpiPrevistaTrocaHelper::resolver(
                $prevInformada !== '' ? $prevInformada : null,
                $dataEntrega,
                $epiId,
                $caNorm
            );
            $out[] = [
                'adms_sst_epi_id' => $epiId,
                'quantidade' => max(1, (int) ($row['quantidade'] ?? 1)),
                'ca_utilizado' => $caNorm,
                'tamanho' => $tam !== '' ? $tam : null,
                'data_prevista_troca' => $prev,
                'observacoes' => trim((string) ($row['observacoes'] ?? '')),
            ];
        }

        return $out;
    }
}
