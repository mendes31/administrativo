<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Models\Services\SstEquipamentoNaoConformidadeService;
use App\adms\Views\Services\LoadViewService;

class SstExecuteEquipamentoVistoria
{
    private const ENTITY = 'equipamento_vistorias';

    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentoVistoriasRepository();
        $vistoria = $repo->getById($id);
        if (!$vistoria) {
            $_SESSION['msg'] = 'Vistoria não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-minhas-equipamento-vistorias');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($vistoria['status'] ?? '') !== 'Concluída') {
            $action = (string) ($_POST['form_action'] ?? 'conclude');
            if ($action === 'upload_fotos') {
                $this->uploadFotos($id);
                return;
            }
            $this->conclude($id, $repo);
            return;
        }

        $readonly = ($vistoria['status'] ?? '') === 'Concluída';
        if (!$readonly) {
            $repo->markEmAndamento($id, (int) ($_SESSION['user_id'] ?? 0));
            $vistoria = $repo->getById($id) ?? $vistoria;
        }

        $this->data['vistoria'] = $vistoria;
        $this->data['respostas'] = $repo->getRespostas($id);
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity(self::ENTITY, $id);
        $ncService = new SstEquipamentoNaoConformidadeService();
        // Vistorias NC antigas (antes do módulo): gera NCs na primeira abertura.
        if ($readonly && ($vistoria['resultado'] ?? '') === 'Não conforme') {
            $ncService->createFromVistoria($id);
        }
        $this->data['nao_conformidades'] = (new SstEquipamentoNaoConformidadesRepository())->getByVistoriaId($id);
        $this->data['readonly'] = $readonly;
        $pageElements = [
            'title_head' => $readonly ? 'Vistoria concluída - SST' : 'Executar vistoria - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => [
                'SstExecuteEquipamentoVistoria',
                'SstExportEquipamentoVistoriaPdf',
                'SstViewAnexo',
                'SstViewEquipamentoNaoConformidade',
                'SstListEquipamentoNaoConformidades',
                'SstCreateEquipamentoAcaoCorretiva',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/vistoria_execute', $this->data))->loadView();
    }

    private function uploadFotos(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_vistoria', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            exit;
        }

        $upload = new SstAnexosUploadService();
        $toDelete = $_POST['delete_anexos'] ?? [];
        $hadDeletes = is_array($toDelete) && $toDelete !== [];
        $upload->processDeletions($toDelete, self::ENTITY, $id);
        $n = $upload->processUploads(self::ENTITY, $id, 'fotos', true);

        if ($n > 0) {
            $_SESSION['msg'] = $n === 1 ? '1 foto salva com data e hora.' : "{$n} fotos salvas com data e hora.";
            $_SESSION['msg_type'] = 'success';
        } elseif ($hadDeletes) {
            $_SESSION['msg'] = 'Fotos atualizadas.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Nenhuma foto nova foi enviada. Use JPG, PNG, GIF ou WEBP (máx. 10 MB).';
            $_SESSION['msg_type'] = 'warning';
        }
        $qs = (!empty($_GET['from']) && $_GET['from'] === 'qr') ? '?from=qr' : '';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id . $qs);
        exit;
    }

    private function conclude(int $id, SstEquipamentoVistoriasRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_vistoria', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            exit;
        }

        $upload = new SstAnexosUploadService();
        $upload->processDeletions($_POST['delete_anexos'] ?? [], self::ENTITY, $id);
        $upload->processUploads(self::ENTITY, $id, 'fotos', true);

        $respostas = [];
        foreach ($_POST['resposta'] ?? [] as $respId => $valor) {
            $respostas[] = [
                'id' => (int) $respId,
                'resposta' => $valor ?: null,
                'observacao' => $_POST['obs_item'][$respId] ?? null,
            ];
        }
        foreach ($respostas as $r) {
            if (empty($r['resposta'])) {
                $_SESSION['msg'] = 'Preencha todos os itens obrigatórios do checklist.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
                exit;
            }
        }
        if (empty($_POST['aceite_assinatura'])) {
            $_SESSION['msg'] = 'Confirme a declaração de realização da vistoria para concluir.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            exit;
        }
        $ok = $repo->saveRespostasAndConclude(
            $id,
            $respostas,
            $_POST['observacao'] ?? null,
            (int) ($_SESSION['user_id'] ?? 0),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        );
        if ($ok) {
            $createdNcs = (new SstEquipamentoNaoConformidadeService())->createFromVistoria($id);
            if ($createdNcs !== []) {
                $n = count($createdNcs);
                $_SESSION['msg'] = 'Vistoria concluída. '
                    . ($n === 1
                        ? 'Foi aberta 1 não conformidade para tratamento.'
                        : "Foram abertas {$n} não conformidades para tratamento.");
            } else {
                $_SESSION['msg'] = 'Vistoria concluída.';
            }
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao salvar vistoria.';
            $_SESSION['msg_type'] = 'danger';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
        exit;
    }
}
