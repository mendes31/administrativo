<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativosPermissionService;
use App\adms\Models\Services\InformativoPublishNotifier;
use App\adms\Models\Services\WhatsappNotificationService;
use App\adms\Views\Services\LoadViewService;

class CreateInformativo
{
    private array|string|null $data = null;

    public function index()
    {
        $this->data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }
        
        $repo = new InformativosRepository();
        $this->data['categorias'] = $repo->getCategorias();
        $this->configureDepartmentsForForm();
        $this->data['all_departments_for_notify'] = (new DepartmentsRepository())->getAllDepartmentsSelect();

        $pageElements = [
            'title_head' => 'Criar Informativo',
            'menu' => 'create-informativo',
            'buttonPermission' => ['CreateInformativo'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/informativos/create', $this->data);
        $loadView->loadView();
    }

    /**
     * Super usuário: todos os departamentos. Demais: só o da sessão (somente leitura na view).
     */
    private function configureDepartmentsForForm(): void
    {
        $deptRepo = new DepartmentsRepository();
        $all = $deptRepo->getAllDepartmentsSelect();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();

        if (UserAccessHelper::hasFullSystemAccess()) {
            $this->data['departments'] = $all;
            $this->data['department_select_locked'] = false;
            $this->data['cannot_create_no_department'] = false;

            return;
        }

        if ($userDept !== null) {
            $this->data['departments'] = array_values(array_filter(
                $all,
                static fn (array $d): bool => (int) ($d['id'] ?? 0) === $userDept
            ));
            if ($this->data['departments'] === []) {
                $this->data['departments'] = [['id' => $userDept, 'name' => 'Departamento #' . $userDept]];
            }
            $this->data['department_select_locked'] = true;
            $this->data['cannot_create_no_department'] = false;

            return;
        }

        $this->data['departments'] = [];
        $this->data['department_select_locked'] = true;
        $this->data['cannot_create_no_department'] = true;
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('create_informativo', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro de validação CSRF!</div>';
            return;
        }

        $titulo = trim(TextEncodingHelper::decodeEntities((string)($_POST['titulo'] ?? '')));
        $conteudo = trim(TextEncodingHelper::decodeEntities((string)($_POST['conteudo'] ?? '')));
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $categoriaNome = trim(TextEncodingHelper::decodeEntities((string)($_POST['categoria'] ?? '')));
        $postedDept = (int)($_POST['department_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        $departmentId = InformativosPermissionService::resolveCreateDepartmentId($postedDept, $userId, $userDept);
        if ($departmentId === null || $departmentId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não é possível publicar: seu usuário não possui departamento vinculado. Solicite ao administrador ou use um perfil com permissão total.</div>';

            return;
        }
        $publishAt = trim($_POST['publish_at'] ?? '');
        $expireAt = trim($_POST['expire_at'] ?? '');
        $urgente = isset($_POST['urgente']);
        $requiresAck = isset($_POST['requires_ack']);
        $ativo = isset($_POST['ativo']);
        $notificar = isset($_POST['notificar']);
        $notifyDepartments = $_POST['notify_departments'] ?? [];

        if (empty($titulo)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O título é obrigatório!</div>';
            return;
        }
        if (empty($conteudo)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O conteúdo é obrigatório!</div>';
            return;
        }
        if ($categoriaId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A categoria é obrigatória!</div>';
            return;
        }
        if ($departmentId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">O departamento é obrigatório!</div>';
            return;
        }

        $now = new \DateTime('now');
        $publishDt = null; $expireDt = null;
        if (!empty($publishAt)) {
            $publishDt = new \DateTime($publishAt);
        }
        if (!empty($expireAt)) {
            $expireDt = new \DateTime($expireAt);
            if ($publishDt && $expireDt <= $publishDt) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A data de expiração deve ser maior que a data de publicação!</div>';
                return;
            }
            if (!$publishDt && $expireDt <= $now) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">A expiração deve ser maior que agora quando não há publicação futura.</div>';
                return;
            }
        }

        $imagem = null;
        if (isset($_FILES['imagem'])) {
            if ($_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $imagem = $this->uploadFile($_FILES['imagem'], 'imagens');
                if ($imagem === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload da imagem. Verifique o tipo e tamanho (máx. 5MB).</div>';
                    return;
                }
            } elseif ($_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload da imagem. Código: '.(int)$_FILES['imagem']['error'].'</div>';
                return;
            }
        }

        $anexo = null;
        if (isset($_FILES['anexo'])) {
            if ($_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
                $anexo = $this->uploadFile($_FILES['anexo'], 'anexos');
                if ($anexo === null) {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro ao fazer upload do anexo. Verifique o tipo e tamanho (máx. 5MB).</div>';
                    return;
                }
            } elseif ($_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload do anexo. Código: '.(int)$_FILES['anexo']['error'].'</div>';
                return;
            }
        }

        $resumo = substr(strip_tags($conteudo), 0, 150);
        if (strlen(strip_tags($conteudo)) > 150) {
            $resumo .= '...';
        }

        $data = [
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'resumo' => $resumo,
            'categoria' => $categoriaNome,
            'categoria_id' => $categoriaId,
            'department_id' => $departmentId,
            'imagem' => $imagem,
            'anexo' => $anexo,
            'urgente' => $urgente,
            'notificar' => $notificar,
            'requires_ack' => $requiresAck,
            'ativo' => $ativo,
            'publish_at' => $publishDt? $publishDt->format('Y-m-d H:i:s') : null,
            'expire_at' => $expireDt? $expireDt->format('Y-m-d H:i:s') : null,
            'usuario_id' => $_SESSION['user_id'] ?? 1,
        ];

        $repo = new InformativosRepository();
        
        try {
            $id = $repo->createInformativo($data);
            
            if ($id) {
                // Atualizar departamentos alvo de notificação (se houver)
                $repo->replaceNotifyDepartments((int)$id, $notifyDepartments);

                // Push PWA + registro de envio (independente do checkbox WhatsApp)
                if ($ativo && ($publishDt === null || $publishDt <= $now)) {
                    InformativoPublishNotifier::notifyPublished((int) $id);
                }

                // Notificar via WhatsApp se marcado para notificação e ativo
                if ($notificar && $ativo) {
                    WhatsappNotificationService::notificarInformativoUrgente((int)$id);
                }

                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo criado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
                exit;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar informativo!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar informativo: ' . $e->getMessage() . '</div>';
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
        if ($folder === 'imagens') {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowedExtensions)) {
                return null;
            }
        }
        // Limite alinhado ao php.ini (upload_max_filesize/post_max_size). Usar 20MB para anexos/imagens.
        $maxSize = 20 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return null;
        }
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . ($extension ? '.' . $extension : '');
        $filepath = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            if ($oldFile) {
                $oldPath = $basePath . '/public/adms/uploads/' . $oldFile;
                if (file_exists($oldPath)) { @unlink($oldPath); }
            }
            return $folder . '/' . $filename;
        }
        return null;
    }
} 