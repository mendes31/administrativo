<?php

declare(strict_types=1);

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdSolicitacoesTitularesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\LgpdTitularRights;
use App\adms\Views\Services\LoadViewService;

class LgpdSolicitacoesTitularesView
{
    private const CSRF = 'lgpd_solicitacao_atender';

    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $id = $this->resolveId((int) $id);
        $repo = new LgpdSolicitacoesTitularesRepository();
        $row = $repo->getById($id);
        if ($row === null) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
            $this->salvar($id, $repo);

            return;
        }

        $this->show($row);
    }

    private function salvar(int $id, LgpdSolicitacoesTitularesRepository $repo): void
    {
        if (!CSRFHelper::validateCSRFToken(self::CSRF, (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
            exit;
        }

        $ok = $repo->updateAtendimento($id, $_POST, (int) ($_SESSION['user_id'] ?? 0));
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Atendimento atualizado.'
            : 'Não foi possível gravar o atendimento.';
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
        exit;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function show(array $row): void
    {
        $direitos = json_decode((string) ($row['direitos_json'] ?? ''), true);
        $this->data['solicitacao'] = $row;
        $this->data['direitos'] = is_array($direitos) ? $direitos : [];
        $this->data['catalogo'] = LgpdTitularRights::catalog();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken(self::CSRF);
        $this->data['log_resumo'] = LogResumoService::getResumo(
            'lgpd_solicitacoes_titulares',
            (int) $row['id'],
            $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . (int) $row['id']
        );

        $pageElements = [
            'title_head' => 'Solicitação ' . ($row['protocolo'] ?? ''),
            'menu' => 'lgpd-solicitacoes-titulares',
            'buttonPermission' => ['LgpdSolicitacoesTitulares', 'LgpdSolicitacoesTitularesView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/lgpd/solicitacoes/view', $this->data))->loadView();
    }

    private function resolveId(int $id): int
    {
        if ($id > 0) {
            return $id;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (preg_match('~/lgpd-solicitacoes-titulares-view/(\d+)~', $uri, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
