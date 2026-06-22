<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Views\Services\LoadViewService;

class SstExecuteEquipamentoVistoria
{
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
        if (($vistoria['status'] ?? '') === 'Concluída') {
            $this->data['vistoria'] = $vistoria;
            $this->data['respostas'] = $repo->getRespostas($id);
            $this->data['readonly'] = true;
            $pageElements = [
                'title_head' => 'Vistoria concluída - SST',
                'menu' => 'sst-minhas-equipamento-vistorias',
                'buttonPermission' => ['SstExecuteEquipamentoVistoria'],
            ];
            $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
            (new LoadViewService('adms/Views/sst/equipamentos/vistoria_execute', $this->data))->loadView();
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->conclude($id, $repo);
            return;
        }
        $repo->markEmAndamento($id, (int) ($_SESSION['user_id'] ?? 0));
        $this->data['vistoria'] = $repo->getById($id);
        $this->data['respostas'] = $repo->getRespostas($id);
        $this->data['readonly'] = false;
        $pageElements = [
            'title_head' => 'Executar vistoria - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => ['SstExecuteEquipamentoVistoria'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/vistoria_execute', $this->data))->loadView();
    }

    private function conclude(int $id, SstEquipamentoVistoriasRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_vistoria', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
            exit;
        }
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
        $_SESSION['msg'] = $ok ? 'Vistoria concluída.' : 'Erro ao salvar vistoria.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $id);
        exit;
    }
}
