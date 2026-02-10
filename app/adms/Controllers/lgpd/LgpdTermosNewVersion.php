<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class LgpdTermosNewVersion
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_new_version_lgpd_termo', $this->data['form']['csrf_token'])) {
            $this->createNewVersion();
        } else {
            $repo = new LgpdTermosRepository();
            $this->data['form'] = $repo->getById((int)$id);

            if (!$this->data['form']) {
                GenerateLog::generateLog('error', 'Termo LGPD não encontrado para nova versão', ['id' => (int)$id]);
                $_SESSION['error'] = "Termo LGPD não encontrado!";
                header("Location: {$_ENV['URL_ADM']}lgpd-termos");
                return;
            }

            // Guardar a versão original para validar no submit
            $this->data['form']['versao_original'] = $this->data['form']['versao'] ?? '';

            $this->viewForm();
        }
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Nova versão do Termo LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermosNewVersion', 'LgpdTermosView'],
        ];

        // informar à view qual chave de CSRF deve ser usada
        $this->data['csrf_key'] = 'form_new_version_lgpd_termo';

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/edit', $this->data);
        $loadView->loadView();
    }

    private function createNewVersion(): void
    {
        $data = $this->data['form'];

        if (empty($data['versao']) || empty($data['titulo']) || empty($data['conteudo'])) {
            $this->data['errors'][] = "Versão, Título e Conteúdo são obrigatórios.";
            $this->viewForm();
            return;
        }

        // Nova versão precisa ser diferente da versão atual
        if (!empty($this->data['form']['versao_original']) &&
            $data['versao'] === $this->data['form']['versao_original']) {
            $this->data['errors'][] = "A nova versão deve ser diferente da versão atual ({$this->data['form']['versao_original']}).";
            $this->viewForm();
            return;
        }

        $repo = new LgpdTermosRepository();
        $idAnterior = (int)($data['id'] ?? 0);

        $newId = $repo->createNewVersion($idAnterior, $data);

        if ($newId) {
            // Registrar log de inserção da nova versão
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            if ($usuarioId > 0) {
                $novoTermo = $repo->getById($newId) ?? [];
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_termos',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $novoTermo
                );
            }

            $_SESSION['success'] = "Nova versão do termo LGPD criada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}lgpd-termos-view/{$newId}");
            return;
        }

        $this->data['errors'][] = "Erro: Nova versão do termo não foi criada.";
        $this->viewForm();
    }
}


