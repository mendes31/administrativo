<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class LgpdTermosEdit
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_edit_lgpd_termo', $this->data['form']['csrf_token'])) {
            $this->updateTermo();
        } else {
            $repo = new LgpdTermosRepository();
            $this->data['form'] = $repo->getById((int)$id);

            if (!$this->data['form']) {
                GenerateLog::generateLog('error', 'Termo LGPD não encontrado', ['id' => (int)$id]);
                $_SESSION['error'] = "Termo LGPD não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-termos");
                return;
            }

            $this->viewForm();
        }
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Editar Termo LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermosEdit', 'LgpdTermosView'],
        ];

        // chave CSRF padrão para edição simples
        $this->data['csrf_key'] = 'form_edit_lgpd_termo';

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/edit', $this->data);
        $loadView->loadView();
    }

    private function updateTermo(): void
    {
        $data = $this->data['form'];

        if (empty($data['versao']) || empty($data['titulo']) || empty($data['conteudo'])) {
            $this->data['errors'][] = "Versão, Título e Conteúdo são obrigatórios.";
            $this->viewForm();
            return;
        }

        $repo = new LgpdTermosRepository();
        $id = (int)($data['id'] ?? 0);

        $data['publico_canal'] = !empty($data['publico_canal']) ? 1 : 0;
        $canal = $repo->normalizePublicChannelFields($data, $id);
        if ($canal['error'] !== null) {
            $this->data['errors'][] = $canal['error'];
            $this->viewForm();
            return;
        }
        $data['publico_canal'] = $canal['publico_canal'];
        $data['slug_publico'] = $canal['slug_publico'];
        $this->data['form'] = $data;

        // Buscar dados antes da alteração para log
        $dadosAntes = $repo->getById($id) ?? [];

        $result = $repo->update($id, $data);

        if ($result) {
            // Registrar log de alteração (edição sem versionamento)
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            $dadosDepois = $repo->getById($id) ?? [];

            if ($usuarioId > 0 && !empty($dadosDepois)) {
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_termos',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $dadosAntes,
                    $dadosDepois
                );
            }

            $_SESSION['success'] = "Termo LGPD editado com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-termos-view/{$id}");
            return;
        }

        $this->data['errors'][] = "Erro: Termo não foi editado.";
        $this->viewForm();
    }
}


