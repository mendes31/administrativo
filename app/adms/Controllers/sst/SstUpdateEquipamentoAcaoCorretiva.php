<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Models\Services\SstEquipamentoNaoConformidadeService;
use App\adms\Views\Services\LoadViewService;

class SstUpdateEquipamentoAcaoCorretiva
{
    private const ENTITY = 'equipamento_acoes_corretivas';

    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentoAcoesCorretivasRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Ação corretiva não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-nao-conformidades');
            exit;
        }

        $ncId = (int) ($item['adms_sst_equipamento_nao_conformidade_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id, $ncId, $repo);
            return;
        }

        $this->data['item'] = $item;
        $this->data['nc'] = [
            'id' => $ncId,
            'codigo' => $item['nc_codigo'] ?? '',
            'descricao' => $item['nc_descricao'] ?? '',
            'status' => $item['nc_status'] ?? '',
        ];
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['anexos'] = (new SstAnexosRepository())->getByEntity(self::ENTITY, $id);
        $pageElements = [
            'title_head' => 'Editar ação corretiva - SST',
            'menu' => 'sst-list-equipamento-nao-conformidades',
            'buttonPermission' => ['SstUpdateEquipamentoAcaoCorretiva', 'SstViewAnexo'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/acao_corretiva_form', $this->data))->loadView();
    }

    private function update(int $id, int $ncId, SstEquipamentoAcoesCorretivasRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_acao_corretiva', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $ncId);
            exit;
        }

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($titulo === '') {
            $_SESSION['msg'] = 'Informe o título da ação.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-equipamento-acao-corretiva/' . $id);
            exit;
        }

        $upload = new SstAnexosUploadService();
        $upload->processDeletions($_POST['delete_anexos'] ?? [], self::ENTITY, $id);
        $ok = $repo->update($id, [
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? null,
            'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id'])
                ? (int) $_POST['responsavel_adms_user_id'] : null,
            'prazo' => $_POST['prazo'] ?? null,
            'data_conclusao' => $_POST['data_conclusao'] ?? null,
            'status' => $_POST['status'] ?? 'Pendente',
            'observacoes' => $_POST['observacoes'] ?? null,
        ]);
        $upload->processUploads(self::ENTITY, $id, 'fotos', true);
        (new SstEquipamentoNaoConformidadeService())->afterAcaoCriadaOuAtualizada($ncId);

        $_SESSION['msg'] = $ok ? 'Ação corretiva atualizada.' : 'Erro ao atualizar.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $ncId);
        exit;
    }
}
