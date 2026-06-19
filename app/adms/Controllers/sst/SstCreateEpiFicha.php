<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEpiFichaPdfService;
use App\adms\Models\Services\SstEpiFichaPublishNotifier;
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
        $this->data['item'] = [];
        if (!empty($_GET['adms_user_id'])) {
            $this->data['item']['adms_user_id'] = (int) $_GET['adms_user_id'];
        }
        $this->data['item']['data_entrega'] = date('Y-m-d');
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
            $_SESSION['msg'] = 'Adicione ao menos um EPI à ficha.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-epi-ficha');
            exit;
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
            $prev = trim((string) ($row['data_prevista_troca'] ?? ''));
            if ($prev === '') {
                $prev = SstEpiFichaPdfService::calcPrevistaTroca($dataEntrega, $epiId) ?? '';
            }
            $out[] = [
                'adms_sst_epi_id' => $epiId,
                'quantidade' => max(1, (int) ($row['quantidade'] ?? 1)),
                'ca_utilizado' => trim((string) ($row['ca_utilizado'] ?? '')),
                'data_prevista_troca' => $prev !== '' ? $prev : null,
                'observacoes' => trim((string) ($row['observacoes'] ?? '')),
            ];
        }

        return $out;
    }
}
