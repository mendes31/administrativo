<?php

declare(strict_types=1);

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\LgpdPortalConfigRepository;
use App\adms\Models\Services\LgpdPublicConfig;
use App\adms\Views\Services\LoadViewService;

/**
 * Configuração do portal público LGPD (DPO, empresa, documentos e comitê).
 */
final class LgpdPublicoConfig
{
    private const CSRF = 'form_lgpd_publico_config';

    private array $data = [];

    public function index(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handlePost();

            return;
        }

        $repo = new LgpdPortalConfigRepository();
        $config = $repo->getConfig();

        $this->data = [
            'title_head' => 'Configuração — Portal público LGPD',
            'menu' => 'lgpd-publico-config',
            'buttonPermission' => ['LgpdPublicoConfig', 'LgpdDashboard'],
            'csrf_token' => CSRFHelper::generateCSRFToken(self::CSRF),
            'portal_url' => LgpdPublicConfig::baseUrl(),
            'config' => $config,
            'effective' => [
                'empresa_nome' => $repo->empresaNome(),
                'dpo_nome' => $repo->dpoNome(),
                'dpo_email' => $repo->dpoEmail(),
                'dpo_telefone' => $repo->dpoTelefone(),
                'cartilha_path' => $repo->cartilhaPath(),
                'carta_compromisso_path' => $repo->cartaCompromissoPath(),
                'comite_titulo' => $repo->comiteTitulo(),
                'comite_descricao' => $repo->comiteDescricao(),
            ],
            'comite_membros' => $repo->getComiteMembros(),
            'edit_membro' => null,
        ];

        $editId = (int) ($_GET['edit_membro'] ?? 0);
        if ($editId > 0) {
            $this->data['edit_membro'] = $repo->getComiteMembroById($editId);
        }

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));

        (new LoadViewService('adms/Views/lgpd/publico/config', $this->data))->loadView();
    }

    private function handlePost(): void
    {
        if (!CSRFHelper::validateCSRFToken(self::CSRF, (string) ($_POST['csrf_token'] ?? ''))) {
            $this->flash('Token de segurança inválido. Tente novamente.', 'danger');
            $this->redirect();

            return;
        }

        $action = (string) ($_POST['action'] ?? 'save_config');
        $repo = new LgpdPortalConfigRepository();

        if ($action === 'delete_membro') {
            $id = (int) ($_POST['membro_id'] ?? 0);
            if ($repo->deleteComiteMembro($id)) {
                $this->flash('Membro do comitê removido.', 'success');
            } else {
                $this->flash('Não foi possível remover o membro.', 'danger');
            }
            $this->redirect();

            return;
        }

        if ($action === 'save_membro') {
            $ok = $repo->saveComiteMembro([
                'id' => $_POST['membro_id'] ?? 0,
                'nome' => $_POST['membro_nome'] ?? '',
                'cargo' => $_POST['membro_cargo'] ?? '',
                'email' => $_POST['membro_email'] ?? '',
                'telefone' => $_POST['membro_telefone'] ?? '',
                'ordem' => $_POST['membro_ordem'] ?? 0,
                'ativo' => $_POST['membro_ativo'] ?? '',
            ]);

            if ($ok) {
                $this->flash('Membro do comitê salvo com sucesso.', 'success');
            } else {
                $this->flash('Não foi possível salvar o membro. Verifique nome e e-mail.', 'danger');
            }
            $this->redirect();

            return;
        }

        $ok = $repo->saveConfig([
            'empresa_nome' => $_POST['empresa_nome'] ?? '',
            'dpo_nome' => $_POST['dpo_nome'] ?? '',
            'dpo_email' => $_POST['dpo_email'] ?? '',
            'dpo_telefone' => $_POST['dpo_telefone'] ?? '',
            'cartilha_path' => $_POST['cartilha_path'] ?? '',
            'carta_compromisso_path' => $_POST['carta_compromisso_path'] ?? '',
            'comite_titulo' => $_POST['comite_titulo'] ?? '',
            'comite_descricao' => $_POST['comite_descricao'] ?? '',
        ]);

        if ($ok) {
            $this->flash('Configuração do portal público salva com sucesso.', 'success');
        } else {
            $this->flash('Não foi possível salvar. Verifique o e-mail do DPO e se a migration foi aplicada.', 'danger');
        }

        $this->redirect();
    }

    private function flash(string $message, string $type): void
    {
        $_SESSION['msg'] = $message;
        $_SESSION['msg_type'] = $type;
    }

    private function redirect(): void
    {
        header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/lgpd-publico-config');
        exit;
    }
}
