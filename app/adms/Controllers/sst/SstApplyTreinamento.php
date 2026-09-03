<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstTreinamentoAplicacoesRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Models\Services\SstTreinamentoCertificadoPdfService;
use App\adms\Models\Services\SstTreinamentoStatusService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstApplyTreinamento
{
    private array $data = [];

    public function index(string|int|null $vinculoId = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->apply();
            return;
        }
        $this->loadForm((int) ($vinculoId ?? $_GET['vinculo_id'] ?? 0));
        $pageElements = [
            'title_head' => 'Aplicar Treinamento SST',
            'menu' => 'sst-list-treinamento-vinculos',
            'buttonPermission' => ['SstApplyTreinamento'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/treinamento_vinculos/apply', $this->data))->loadView();
    }

    private function loadForm(int $vinculoId): void
    {
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['treinamentos'] = (new SstTreinamentosRepository())->getAll(1, 500, ['status' => 'Ativo']);
        if ($vinculoId > 0) {
            $this->data['vinculo'] = (new SstTreinamentoVinculosRepository())->getById($vinculoId);
        }
    }

    private function apply(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_apply_treinamento_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-apply-treinamento');
            exit;
        }

        $vinculoId = (int) ($_POST['adms_sst_treinamento_vinculo_id'] ?? 0);
        $userId = (int) ($_POST['adms_user_id'] ?? 0);
        $treinamentoId = (int) ($_POST['adms_sst_treinamento_id'] ?? 0);
        $statusAplicacao = ($_POST['status_aplicacao'] ?? 'concluido') === 'agendado' ? 'agendado' : 'concluido';
        $dataRealizacao = trim((string) ($_POST['data_realizacao'] ?? ''));
        $dataAgendada = trim((string) ($_POST['data_agendada'] ?? ''));

        $vinculoRepo = new SstTreinamentoVinculosRepository();
        $vinculo = $vinculoId > 0 ? $vinculoRepo->getById($vinculoId) : null;
        if (!$vinculo && $userId > 0 && $treinamentoId > 0) {
            $vinculo = $vinculoRepo->getByUserAndTreinamento($userId, $treinamentoId);
            $vinculoId = (int) ($vinculo['id'] ?? 0);
        }
        if (!$vinculo || $vinculoId <= 0) {
            $_SESSION['msg'] = 'Vínculo de treinamento não encontrado. Sincronize os vínculos primeiro.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-apply-treinamento');
            exit;
        }

        $treinamento = (new SstTreinamentosRepository())->getById((int) $vinculo['adms_sst_treinamento_id']);
        $validadeMeses = (int) ($treinamento['validade_meses'] ?? 0);
        $dataValidade = null;
        if ($statusAplicacao === 'concluido' && $dataRealizacao !== '') {
            $dataValidade = SstTreinamentoStatusService::calcularDataValidade($dataRealizacao, $validadeMeses > 0 ? $validadeMeses : null);
        }

        $aplicacaoId = (new SstTreinamentoAplicacoesRepository())->create([
            'adms_sst_treinamento_vinculo_id' => $vinculoId,
            'adms_user_id' => (int) $vinculo['adms_user_id'],
            'adms_sst_treinamento_id' => (int) $vinculo['adms_sst_treinamento_id'],
            'data_realizacao' => $statusAplicacao === 'concluido' ? ($dataRealizacao !== '' ? $dataRealizacao : null) : null,
            'data_agendada' => $statusAplicacao === 'agendado' ? ($dataAgendada !== '' ? $dataAgendada : null) : null,
            'nota' => $_POST['nota'] ?? null,
            'instrutor_nome' => $_POST['instrutor_nome'] ?? null,
            'instrutor_registro' => $_POST['instrutor_registro'] ?? null,
            'modalidade_aplicada' => $_POST['modalidade_aplicada'] ?? null,
            'certificado' => null,
            'observacoes' => $_POST['observacoes'] ?? null,
            'status' => $statusAplicacao,
        ]);

        $updateData = array_merge($vinculo, [
            'data_realizacao' => $statusAplicacao === 'concluido' ? ($dataRealizacao !== '' ? $dataRealizacao : $vinculo['data_realizacao'] ?? null) : $vinculo['data_realizacao'] ?? null,
            'data_agendada' => $statusAplicacao === 'agendado' ? ($dataAgendada !== '' ? $dataAgendada : null) : null,
            'data_validade' => $dataValidade ?? $vinculo['data_validade'] ?? null,
            'nota' => $_POST['nota'] ?? null,
            'certificado' => null,
            'observacoes' => $_POST['observacoes'] ?? null,
            'motivo' => !empty($vinculo['data_realizacao']) ? 'reciclagem' : ($vinculo['motivo'] ?? 'primeiro'),
        ]);
        $vinculoRepo->update($vinculoId, $updateData);
        (new SstTreinamentoStatusService())->recalculateVinculo($vinculoId);

        if ($statusAplicacao === 'concluido' && $dataRealizacao !== '') {
            try {
                (new SstTreinamentoCertificadoPdfService())->generateForVinculo(
                    $vinculoId,
                    is_int($aplicacaoId) && $aplicacaoId > 0 ? $aplicacaoId : null
                );
            } catch (\Throwable $e) {
                $_SESSION['msg'] = 'Treinamento registrado, mas o certificado PDF não foi gerado: ' . $e->getMessage();
                $_SESSION['msg_type'] = 'warning';
                SstPendenciasService::invalidateDashboardCache();
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . $vinculoId);
                exit;
            }
        }

        SstPendenciasService::invalidateDashboardCache();

        $_SESSION['msg'] = 'Treinamento registrado com sucesso.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-treinamento-vinculo/' . $vinculoId);
        exit;
    }
}
