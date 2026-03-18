<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\WhatsappNotificationService;
use App\adms\Views\Services\LoadViewService;

class UpdatePolicy
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        $policyId = (int) ($id ?: ($_GET['id'] ?? 0));
        if ($policyId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política inválida.</div>';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);
        if (!$policy) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política não encontrada.</div>';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('update_policy', $this->data['form']['csrf_token'])
        ) {
            $this->save($policyId, $policy, $repo);
            return;
        }

        $this->prepareViewData($policy, $repo);
    }

    private function prepareViewData(array $policy, PoliciesRepository $repo): void
    {
        $this->data['policy'] = $policy;
        $this->data['categorias'] = $repo->getCategorias();

        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['notify_departments'] = $repo->getNotifyDepartmentsIds((int) $policy['id']);

        $pageElements = [
            'title_head'       => 'Editar Política Interna',
            'menu'             => 'gestao_pessoas',
            'buttonPermission' => ['UpdatePolicy'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id, array $oldPolicy, PoliciesRepository $repo): void
    {
        $form = $this->data['form'] ?? [];

        $titulo        = trim($form['titulo'] ?? '');
        $conteudo      = trim($form['conteudo'] ?? '');
        $categoriaId   = (int) ($form['categoria_id'] ?? 0);
        $categoriaNome = trim($form['categoria'] ?? '');
        $departmentId  = (int) ($form['department_id'] ?? 0);
        $publishAt     = trim($form['publish_at'] ?? '');
        $expireAt      = trim($form['expire_at'] ?? '');
        $urgente       = !empty($form['urgente']);
        $requiresAck   = !empty($form['requires_ack']);
        $ativo         = !empty($form['ativo']);
        $notificar     = !empty($form['notificar']);
        $notifyDepartments = $form['notify_departments'] ?? [];

        if ($titulo === '' || $conteudo === '' || $categoriaId <= 0 || $departmentId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Preencha todos os campos obrigatórios.</div>';
            $_SESSION['msg_type'] = 'danger';
            $this->prepareViewData($oldPolicy, $repo);
            return;
        }

        $now = new \DateTime('now');
        $publishDt = null;
        $expireDt  = null;

        if ($publishAt !== '') {
            $publishDt = new \DateTime($publishAt);
        }
        if ($expireAt !== '') {
            $expireDt = new \DateTime($expireAt);
            if ($publishDt && $expireDt <= $publishDt) {
                $_SESSION['msg'] = '<div class="alert alert-danger">A data de expiração deve ser maior que a data de publicação.</div>';
                $_SESSION['msg_type'] = 'danger';
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
            if (!$publishDt && $expireDt <= $now) {
                $_SESSION['msg'] = '<div class="alert alert-danger">A expiração deve ser maior que agora quando não há publicação futura.</div>';
                $_SESSION['msg_type'] = 'danger';
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
        }

        // Uploads: permite trocar imagem/anexo, mantendo os atuais se nada for enviado
        $imagem = $oldPolicy['imagem'] ?? null;
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $upload = $this->uploadFile($_FILES['imagem'], 'policies/imagens', $imagem);
                if ($upload === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning">Erro ao enviar imagem. Verifique tipo/tamanho (máx. 20MB).</div>';
                    $_SESSION['msg_type'] = 'warning';
                    $this->prepareViewData($oldPolicy, $repo);
                    return;
                }
                $imagem = $upload;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning">Erro no upload da imagem. Código: ' . (int) $_FILES['imagem']['error'] . '</div>';
                $_SESSION['msg_type'] = 'warning';
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
        }

        $anexo  = $oldPolicy['anexo'] ?? null;
        if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
                $upload = $this->uploadFile($_FILES['anexo'], 'policies/anexos', $anexo);
                if ($upload === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning">Erro ao enviar anexo. Verifique tipo/tamanho (máx. 20MB).</div>';
                    $_SESSION['msg_type'] = 'warning';
                    $this->prepareViewData($oldPolicy, $repo);
                    return;
                }
                $anexo = $upload;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning">Erro no upload do anexo. Código: ' . (int) $_FILES['anexo']['error'] . '</div>';
                $_SESSION['msg_type'] = 'warning';
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
        }

        $resumo = mb_substr(strip_tags($conteudo), 0, 150);
        if (mb_strlen(strip_tags($conteudo)) > 150) {
            $resumo .= '...';
        }

        $data = [
            'titulo'        => $titulo,
            'conteudo'      => $conteudo,
            'resumo'        => $resumo,
            'categoria'     => $categoriaNome,
            'categoria_id'  => $categoriaId,
            'department_id' => $departmentId,
            'imagem'        => $imagem,
            'anexo'         => $anexo,
            'urgente'       => $urgente,
            'notificar'     => $notificar,
            'requires_ack'  => $requiresAck,
            'ativo'         => $ativo,
            'publish_at'    => $publishDt ? $publishDt->format('Y-m-d H:i:s') : null,
            'expire_at'     => $expireDt ? $expireDt->format('Y-m-d H:i:s') : null,
        ];

        try {
            $ok = $repo->updatePolicy($id, $data);
            if ($ok) {
                // Atualizar departamentos alvo de notificação
                $repo->replaceNotifyDepartments($id, $notifyDepartments);

                // Se estiver marcada para notificar e ativa, disparar notificação via WhatsApp
                if ($notificar && $ativo) {
                    WhatsappNotificationService::notificarPolicyUrgente((int) $id);
                }

                $usuarioId = (int) ($_SESSION['user_id'] ?? 0);
                LogAlteracaoService::registrarAlteracao(
                    'adms_policies',
                    $id,
                    $usuarioId,
                    'update',
                    $oldPolicy,
                    array_merge(['id' => $id], $data)
                );

                $_SESSION['msg'] = '<div class="alert alert-success">Política atualizada com sucesso.</div>';
                $_SESSION['msg_type'] = 'success';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
                exit;
            }

            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao atualizar política.</div>';
            $_SESSION['msg_type'] = 'danger';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao atualizar política: ' . $e->getMessage() . '</div>';
            $_SESSION['msg_type'] = 'danger';
        }

        $this->prepareViewData($oldPolicy, $repo);
    }

    /**
     * Upload de arquivo (imagem ou anexo), opcionalmente removendo o antigo.
     * Cópia do mesmo método usado em CreatePolicy, para manter comportamento idêntico.
     */
    private function uploadFile(array $file, string $folder, ?string $oldFile = null): ?string
    {
        if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $basePath = dirname(__DIR__, 4);
        $uploadDir = $basePath . '/public/adms/uploads/' . $folder . '/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return null;
        }
        if (!is_writable($uploadDir)) {
            return null;
        }

        // Se for imagem, restringir extensões
        if (str_contains($folder, 'imagens')) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowedExtensions, true)) {
                return null;
            }
        }

        // Limite de 20MB (alinhado ao módulo de Informativos/Políticas)
        $maxSize = 20 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            return null;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . ($extension ? '.' . $extension : '');
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            if ($oldFile) {
                $oldPath = $basePath . '/public/adms/uploads/' . $oldFile;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            return $folder . '/' . $filename;
        }

        return null;
    }
}

