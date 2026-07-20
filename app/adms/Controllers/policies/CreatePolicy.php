<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\PolicyPublishNotifier;
use App\adms\Models\Services\WhatsappNotificationService;
use App\adms\Views\Services\LoadViewService;

class CreatePolicy
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $repo = new PoliciesRepository();
        $this->data['categorias'] = $repo->getCategorias();

        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        $pageElements = [
            'title_head'       => 'Cadastrar Política Interna',
            'menu' => 'list-policies',
            'buttonPermission' => ['CreatePolicy'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('create_policy', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro de validação CSRF!</div>';
            $_SESSION['msg_type'] = 'danger';
            return;
        }

        $titulo            = trim(TextEncodingHelper::decodeEntities((string)($_POST['titulo'] ?? '')));
        $conteudo          = trim(TextEncodingHelper::decodeEntities((string)($_POST['conteudo'] ?? '')));
        $categoriaId       = (int) ($_POST['categoria_id'] ?? 0);
        $categoriaNome     = trim(TextEncodingHelper::decodeEntities((string)($_POST['categoria'] ?? '')));
        $departmentId      = (int) ($_POST['department_id'] ?? 0);
        $publishAt         = trim($_POST['publish_at'] ?? '');
        $expireAt          = trim($_POST['expire_at'] ?? '');
        $urgente           = isset($_POST['urgente']);
        $requiresAck       = isset($_POST['requires_ack']);
        $ativo             = isset($_POST['ativo']);
        $notificar         = isset($_POST['notificar']);
        $notifyDepartments = $_POST['notify_departments'] ?? [];

        if ($titulo === '') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O título é obrigatório!</div>';
            $_SESSION['msg_type'] = 'danger';
            return;
        }
        if ($conteudo === '') {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O conteúdo é obrigatório!</div>';
            $_SESSION['msg_type'] = 'danger';
            return;
        }
        if ($categoriaId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A categoria é obrigatória!</div>';
            $_SESSION['msg_type'] = 'danger';
            return;
        }
        if ($departmentId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O departamento responsável é obrigatório!</div>';
            $_SESSION['msg_type'] = 'danger';
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
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A data de expiração deve ser maior que a data de publicação!</div>';
                $_SESSION['msg_type'] = 'danger';
                return;
            }
            if (!$publishDt && $expireDt <= $now) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A expiração deve ser maior que agora quando não há publicação futura.</div>';
                $_SESSION['msg_type'] = 'danger';
                return;
            }
        }

        // Upload de imagem (opcional)
        $imagem = null;
        if (isset($_FILES['imagem'])) {
            if ($_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $imagem = $this->uploadFile($_FILES['imagem'], 'policies/imagens');
                if ($imagem === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload da imagem. Verifique o tipo e tamanho (máx. 20MB).</div>';
                    $_SESSION['msg_type'] = 'warning';
                    return;
                }
            } elseif ($_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload da imagem. Código: ' . (int) $_FILES['imagem']['error'] . '</div>';
                $_SESSION['msg_type'] = 'warning';
                return;
            }
        }

        // Upload de anexo (opcional)
        $anexo = null;
        if (isset($_FILES['anexo'])) {
            if ($_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
                $anexo = $this->uploadFile($_FILES['anexo'], 'policies/anexos');
                if ($anexo === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload do anexo. Verifique o tipo e tamanho (máx. 20MB).</div>';
                    $_SESSION['msg_type'] = 'warning';
                    return;
                }
            } elseif ($_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload do anexo. Código: ' . (int) $_FILES['anexo']['error'] . '</div>';
                $_SESSION['msg_type'] = 'warning';
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
            'usuario_id'    => $_SESSION['user_id'] ?? 1,
        ];

        $repo = new PoliciesRepository();

        try {
            $id = $repo->createPolicy($data);

            if ($id) {
                // Atualizar departamentos alvo de notificação (se houver)
                $repo->replaceNotifyDepartments((int) $id, $notifyDepartments);

                if ($ativo && ($publishDt === null || $publishDt <= $now)) {
                    PolicyPublishNotifier::notifyPublished((int) $id);
                }

                // Notificar via WhatsApp se marcado e ativo
                if ($notificar && $ativo) {
                    WhatsappNotificationService::notificarPolicyUrgente((int) $id);
                }

                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Política interna cadastrada com sucesso!</div>';
                $_SESSION['msg_type'] = 'success';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
                exit;
            }

            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao cadastrar política interna!</div>';
            $_SESSION['msg_type'] = 'danger';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao cadastrar política interna: ' . $e->getMessage() . '</div>';
            $_SESSION['msg_type'] = 'danger';
        }
    }

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

        // Limite de 20MB (alinhado ao módulo de Informativos)
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

