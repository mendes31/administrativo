<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstAnexosRepository;
use App\adms\Models\Repository\SstEquipamentoAcoesCorretivasRepository;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstAnexosUploadService;
use App\adms\Models\Services\SstEquipamentoNaoConformidadeService;
use App\adms\Views\Services\LoadViewService;

class SstCreateEquipamentoAcaoCorretiva
{
    private const ENTITY = 'equipamento_acoes_corretivas';

    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $ncId = (int) ($_GET['nc_id'] ?? 0);
        $nc = (new SstEquipamentoNaoConformidadesRepository())->getById($ncId);
        if (!$nc) {
            $_SESSION['msg'] = 'NC não informada ou não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-nao-conformidades');
            exit;
        }
        if (!in_array($nc['status'] ?? '', ['Aberta', 'Em tratamento'], true)) {
            $_SESSION['msg'] = 'Não é possível criar ação corretiva em NC encerrada/cancelada.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $ncId);
            exit;
        }

        $this->data['nc'] = $nc;
        $this->data['item'] = [
            'adms_sst_equipamento_nao_conformidade_id' => $ncId,
            'status' => 'Pendente',
        ];
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['anexos'] = [];
        $pageElements = [
            'title_head' => 'Nova ação corretiva - SST',
            'menu' => 'sst-list-equipamento-nao-conformidades',
            'buttonPermission' => ['SstCreateEquipamentoAcaoCorretiva'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/acao_corretiva_form', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_acao_corretiva', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-nao-conformidades');
            exit;
        }

        $ncId = (int) ($_POST['adms_sst_equipamento_nao_conformidade_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($ncId <= 0 || $titulo === '') {
            $_SESSION['msg'] = 'Preencha os campos obrigatórios.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento-acao-corretiva?nc_id=' . $ncId);
            exit;
        }

        $newId = (new SstEquipamentoAcoesCorretivasRepository())->create([
            'adms_sst_equipamento_nao_conformidade_id' => $ncId,
            'titulo' => $titulo,
            'descricao' => $_POST['descricao'] ?? null,
            'responsavel_adms_user_id' => !empty($_POST['responsavel_adms_user_id'])
                ? (int) $_POST['responsavel_adms_user_id'] : null,
            'prazo' => $_POST['prazo'] ?? null,
            'data_conclusao' => $_POST['data_conclusao'] ?? null,
            'status' => $_POST['status'] ?? 'Pendente',
            'observacoes' => $_POST['observacoes'] ?? null,
        ]);

        if ($newId) {
            (new SstAnexosUploadService())->processUploads(self::ENTITY, $newId, 'fotos', true);
            (new SstEquipamentoNaoConformidadeService())->afterAcaoCriadaOuAtualizada($ncId);
            $_SESSION['msg'] = 'Ação corretiva cadastrada.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-nao-conformidade/' . $ncId);
            exit;
        }

        $_SESSION['msg'] = 'Não foi possível salvar a ação corretiva.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-equipamento-acao-corretiva?nc_id=' . $ncId);
        exit;
    }
}
