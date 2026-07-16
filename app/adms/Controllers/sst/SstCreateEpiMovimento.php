<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Models\Services\SstEpiEstoqueService;
use App\adms\Views\Services\LoadViewService;

class SstCreateEpiMovimento
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        $this->loadForm();
        (new LoadViewService('adms/Views/sst/epi_estoque/movimento_form', $this->data))->loadView();
    }

    private function loadForm(): void
    {
        $epiIdLocked = (int) ($_GET['adms_sst_epi_id'] ?? 0);
        $tipoDefault = in_array($_GET['tipo'] ?? '', ['Entrada', 'Saída', 'Devolução', 'Ajuste'], true) ? $_GET['tipo'] : 'Entrada';
        $movRepo = new SstEpiMovimentosRepository();
        $epiRepo = new SstEpisRepository();

        $this->data['epi_locked'] = $epiIdLocked > 0;
        $this->data['multi_mode'] = $epiIdLocked <= 0;
        $this->data['epis'] = $epiRepo->getAll(1, 500, ['status' => 'Ativo']);

        if ($epiIdLocked > 0) {
            $epi = $epiRepo->getById($epiIdLocked);
            if (!$epi) {
                $_SESSION['msg'] = 'EPI não encontrado.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epis');
                exit;
            }
            $this->data['epi_locked_item'] = $epi;
            $this->data['saldo_atual'] = $movRepo->hasTable() ? $movRepo->getSaldoCalculado($epiIdLocked) : (int) ($epi['estoque_atual'] ?? 0);
        }

        $casPorEpi = [];
        foreach ($this->data['epis'] as $ep) {
            $epId = (int) ($ep['id'] ?? 0);
            $casPorEpi[$epId] = $movRepo->getSaldoPorCaPorEpi($epId);
        }
        $this->data['cas_estoque_por_epi_json'] = json_encode($casPorEpi, JSON_UNESCAPED_UNICODE);

        $this->data['item'] = [
            'adms_sst_epi_id' => $epiIdLocked ?: '',
            'tipo_movimento' => $tipoDefault,
            'data_movimento' => date('Y-m-d'),
            'quantidade' => 1,
        ];

        $this->data['motivos_saida'] = \App\adms\Helpers\SstEpiMovimentoHelper::motivosSaida();
        $this->data['motivos_ajuste'] = \App\adms\Helpers\SstEpiMovimentoHelper::motivosAjuste();

        $pageElements = [
            'title_head' => 'Movimentação de estoque EPI',
            'menu' => 'sst-list-epi-movimentos',
            'buttonPermission' => ['SstCreateEpiMovimento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_epi_movimento_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Sessão expirada. Tente novamente.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-movimento');
            exit;
        }

        $itensRaw = $_POST['itens'] ?? null;
        if (is_array($itensRaw) && $itensRaw !== []) {
            $this->saveMulti();
            return;
        }

        $this->saveSingle();
    }

    private function saveSingle(): void
    {
        $lockedId = (int) ($_POST['epi_locked_id'] ?? 0);
        $epiId = (int) ($_POST['adms_sst_epi_id'] ?? 0);
        if ($lockedId > 0) {
            $epiId = $lockedId;
        }

        $tipo = (string) ($_POST['tipo_movimento'] ?? '');
        $payload = [
            'adms_sst_epi_id' => $epiId,
            'tipo_movimento' => $tipo,
            'quantidade' => (int) ($_POST['quantidade'] ?? 1),
            'valor_unitario' => $_POST['valor_unitario'] ?? null,
            'data_movimento' => trim((string) ($_POST['data_movimento'] ?? '')),
            'ca_numero' => trim((string) ($_POST['ca_numero'] ?? '')),
            'ca_validade' => trim((string) ($_POST['ca_validade'] ?? '')),
            'documento_ref' => trim((string) ($_POST['documento_ref'] ?? '')),
            'observacoes' => trim((string) ($_POST['observacoes'] ?? '')),
            'motivo' => trim((string) ($_POST['motivo'] ?? '')),
            'justificativa' => trim((string) ($_POST['justificativa'] ?? '')),
        ];
        if ($tipo === 'Ajuste') {
            $payload['saldo_novo'] = (int) ($_POST['saldo_novo'] ?? -1);
        }

        $result = (new SstEpiEstoqueService())->registrarMovimento($payload);
        if (!$result['ok']) {
            $_SESSION['msg'] = $result['error'] ?? 'Erro ao registrar movimentação.';
            $_SESSION['msg_type'] = 'danger';
            $redirect = $lockedId > 0
                ? 'sst-create-epi-movimento?adms_sst_epi_id=' . $lockedId
                : 'sst-create-epi-movimento';
            header('Location: ' . $_ENV['URL_ADM'] . $redirect);
            exit;
        }

        $doc = trim((string) ($result['doc_codigo'] ?? ''));
        $_SESSION['msg'] = $doc !== ''
            ? 'Movimentação ' . $doc . ' registrada com sucesso.'
            : 'Movimentação registrada com sucesso.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-movimentos?adms_sst_epi_id=' . $epiId);
        exit;
    }

    private function saveMulti(): void
    {
        $tipo = (string) ($_POST['tipo_movimento'] ?? '');
        if (!in_array($tipo, ['Entrada', 'Saída', 'Devolução'], true)) {
            $_SESSION['msg'] = 'No registro em lote, use Entrada, Saída ou Devolução.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-movimento');
            exit;
        }

        $dataMov = trim((string) ($_POST['data_movimento'] ?? ''));
        $documentoRef = trim((string) ($_POST['documento_ref'] ?? ''));
        $obsGeral = trim((string) ($_POST['observacoes'] ?? ''));
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        $justificativa = trim((string) ($_POST['justificativa'] ?? ''));
        $itens = $this->parseItens($_POST['itens'] ?? []);
        if ($itens === []) {
            $_SESSION['msg'] = 'Adicione ao menos um EPI com Nº CA.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-movimento');
            exit;
        }

        $svc = new SstEpiEstoqueService();
        $registrados = 0;
        $docs = [];
        foreach ($itens as $idx => $item) {
            $obs = $obsGeral;
            if ($item['observacoes'] !== '') {
                $obs = ($obs !== '' ? $obs . ' — ' : '') . $item['observacoes'];
            }
            $payload = [
                'adms_sst_epi_id' => (int) $item['adms_sst_epi_id'],
                'tipo_movimento' => $tipo,
                'quantidade' => (int) $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'] ?? null,
                'data_movimento' => $dataMov,
                'ca_numero' => (string) $item['ca_numero'],
                'ca_validade' => (string) ($item['ca_validade'] ?? ''),
                'documento_ref' => $documentoRef,
                'observacoes' => $obs,
                'motivo' => $motivo,
                'justificativa' => $justificativa,
            ];
            $result = $svc->registrarMovimento($payload);
            if (!$result['ok']) {
                $linha = $idx + 1;
                $prefix = $registrados > 0
                    ? $registrados . ' movimentação(ões) registrada(s) antes do erro. '
                    : '';
                $_SESSION['msg'] = $prefix . 'Item ' . $linha . ': ' . ($result['error'] ?? 'Erro ao registrar.');
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-movimentos');
                exit;
            }
            if (!empty($result['doc_codigo'])) {
                $docs[] = (string) $result['doc_codigo'];
            }
            $registrados++;
        }

        $docsTxt = $docs !== [] ? ' (' . implode(', ', $docs) . ')' : '';
        $_SESSION['msg'] = $registrados === 1
            ? 'Movimentação registrada com sucesso' . $docsTxt . '.'
            : $registrados . ' movimentações registradas com sucesso' . $docsTxt . '.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-movimentos');
        exit;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseItens(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $epiId = (int) ($row['adms_sst_epi_id'] ?? 0);
            $ca = trim((string) ($row['ca_numero'] ?? ''));
            if ($epiId <= 0 || $ca === '') {
                continue;
            }
            $out[] = [
                'adms_sst_epi_id' => $epiId,
                'quantidade' => max(1, (int) ($row['quantidade'] ?? 1)),
                'valor_unitario' => $row['valor_unitario'] ?? null,
                'ca_numero' => $ca,
                'ca_validade' => trim((string) ($row['ca_validade'] ?? '')),
                'observacoes' => trim((string) ($row['observacoes'] ?? '')),
            ];
        }

        return $out;
    }
}
