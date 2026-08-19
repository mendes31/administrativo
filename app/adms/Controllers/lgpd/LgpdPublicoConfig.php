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
    private const MAX_PDF_BYTES = 50 * 1024 * 1024;
    private const REL_DIR = 'storage/lgpd/publico';

    private array $data = [];

    public function index(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handlePost();

            return;
        }

        $repo = new LgpdPortalConfigRepository();
        $config = $repo->getConfig();
        $docs = LgpdPublicConfig::documentPaths();

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
            'docs_status' => [
                'cartilha' => $docs['cartilha'] !== null,
                'carta' => $docs['carta'] !== null,
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
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxUpload = ini_get('upload_max_filesize') ?: '?';
            $maxPost = ini_get('post_max_size') ?: '?';
            $this->flash(
                "O ficheiro excede o limite do servidor (upload_max_filesize={$maxUpload}, post_max_size={$maxPost}). "
                . 'Reduza o tamanho do PDF ou peça ao administrador para aumentar o limite no php.ini.',
                'danger'
            );
            $this->redirect();

            return;
        }

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

        if ($action === 'delete_pdf') {
            $tipo = (string) ($_POST['pdf_tipo'] ?? '');
            $result = $this->deletePdf($tipo, $repo);
            $this->flash($result['message'], $result['ok'] ? 'success' : 'danger');
            $this->redirect();

            return;
        }

        $cartilhaPath = trim((string) ($_POST['cartilha_path'] ?? ''));
        $cartaPath = trim((string) ($_POST['carta_compromisso_path'] ?? ''));
        $uploadNotes = [];

        $cartilhaUpload = $this->storePdfUpload($_FILES['cartilha_file'] ?? null, 'cartilha.pdf');
        if ($cartilhaUpload['error'] !== null) {
            $this->flash($cartilhaUpload['error'], 'danger');
            $this->redirect();

            return;
        }
        if ($cartilhaUpload['path'] !== null) {
            $cartilhaPath = $cartilhaUpload['path'];
            $uploadNotes[] = 'cartilha';
        }

        $cartaUpload = $this->storePdfUpload($_FILES['carta_file'] ?? null, 'carta-compromisso.pdf');
        if ($cartaUpload['error'] !== null) {
            $this->flash($cartaUpload['error'], 'danger');
            $this->redirect();

            return;
        }
        if ($cartaUpload['path'] !== null) {
            $cartaPath = $cartaUpload['path'];
            $uploadNotes[] = 'carta de compromisso';
        }

        $ok = $repo->saveConfig([
            'empresa_nome' => $_POST['empresa_nome'] ?? '',
            'dpo_nome' => $_POST['dpo_nome'] ?? '',
            'dpo_email' => $_POST['dpo_email'] ?? '',
            'dpo_telefone' => $_POST['dpo_telefone'] ?? '',
            'cartilha_path' => $cartilhaPath,
            'carta_compromisso_path' => $cartaPath,
            'comite_titulo' => $_POST['comite_titulo'] ?? '',
            'comite_descricao' => $_POST['comite_descricao'] ?? '',
        ]);

        if ($ok) {
            $msg = 'Configuração do portal público salva com sucesso.';
            if ($uploadNotes !== []) {
                $msg .= ' Upload: ' . implode(' e ', $uploadNotes) . '.';
            }
            $this->flash($msg, 'success');
        } else {
            $this->flash('Não foi possível salvar. Verifique o e-mail do DPO e se a migration foi aplicada.', 'danger');
        }

        $this->redirect();
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private function deletePdf(string $tipo, LgpdPortalConfigRepository $repo): array
    {
        $map = [
            'cartilha' => ['field' => 'cartilha_path', 'label' => 'Cartilha', 'docKey' => 'cartilha'],
            'carta' => ['field' => 'carta_compromisso_path', 'label' => 'Carta de compromisso', 'docKey' => 'carta'],
        ];
        if (!isset($map[$tipo])) {
            return ['ok' => false, 'message' => 'Tipo de documento inválido.'];
        }

        $meta = $map[$tipo];
        $docs = LgpdPublicConfig::documentPaths();
        $absolute = $docs[$meta['docKey']] ?? null;
        if ($absolute !== null && is_file($absolute) && !@unlink($absolute)) {
            return ['ok' => false, 'message' => 'Não foi possível remover o ficheiro PDF.'];
        }

        $config = $repo->getConfig();
        $payload = [
            'empresa_nome' => (string) ($config['empresa_nome'] ?? ''),
            'dpo_nome' => (string) ($config['dpo_nome'] ?? ''),
            'dpo_email' => (string) ($config['dpo_email'] ?? ''),
            'dpo_telefone' => (string) ($config['dpo_telefone'] ?? ''),
            'cartilha_path' => (string) ($config['cartilha_path'] ?? ''),
            'carta_compromisso_path' => (string) ($config['carta_compromisso_path'] ?? ''),
            'comite_titulo' => (string) ($config['comite_titulo'] ?? ''),
            'comite_descricao' => (string) ($config['comite_descricao'] ?? ''),
        ];
        $payload[$meta['field']] = '';

        if (!$repo->saveConfig($payload)) {
            return [
                'ok' => false,
                'message' => $meta['label'] . ' removida do disco, mas falhou ao limpar o caminho na configuração.',
            ];
        }

        LgpdPortalConfigRepository::clearCache();

        return [
            'ok' => true,
            'message' => $meta['label'] . ' removida. O cartão deixa de aparecer na página pública.',
        ];
    }

    /**
     * @param array<string, mixed>|null $file
     * @return array{path:?string,error:?string}
     */
    private function storePdfUpload(?array $file, string $targetFilename): array
    {
        if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Falha no upload do PDF (código ' . (int) $file['error'] . ').'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['path' => null, 'error' => 'Upload inválido.'];
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_PDF_BYTES) {
            return ['path' => null, 'error' => 'O PDF não pode ultrapassar 50 MB.'];
        }

        $originalName = (string) ($file['name'] ?? '');
        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return ['path' => null, 'error' => 'Apenas ficheiros PDF são aceites (' . $targetFilename . ').'];
        }

        if (!$this->looksLikePdf($tmp)) {
            return ['path' => null, 'error' => 'O ficheiro enviado não parece ser um PDF válido.'];
        }

        $dir = $this->projectRoot() . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::REL_DIR);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => 'Não foi possível criar a pasta de documentos públicos LGPD.'];
        }
        if (!is_writable($dir)) {
            return ['path' => null, 'error' => 'A pasta storage/lgpd/publico não tem permissão de escrita.'];
        }

        $dest = $dir . DIRECTORY_SEPARATOR . $targetFilename;
        if (!move_uploaded_file($tmp, $dest)) {
            return ['path' => null, 'error' => 'Não foi possível gravar o PDF em disco.'];
        }

        return ['path' => self::REL_DIR . '/' . $targetFilename, 'error' => null];
    }

    private function looksLikePdf(string $tmpPath): bool
    {
        $fh = @fopen($tmpPath, 'rb');
        if ($fh === false) {
            return false;
        }
        $header = (string) fread($fh, 5);
        fclose($fh);
        if (!str_starts_with($header, '%PDF-')) {
            return false;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string) finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                if ($mime !== '' && $mime !== 'application/pdf' && $mime !== 'application/octet-stream') {
                    return false;
                }
            }
        }

        return true;
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 4);
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
