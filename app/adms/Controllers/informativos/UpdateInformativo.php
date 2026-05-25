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

class UpdateInformativo
{
    private array|string|null $data = null;

    public function index(string|int $id = null)
    {
        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $userId = InformativosPermissionService::sessionUserId();
        $userDept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($informativo, $userId, $userDept)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você não tem permissão para editar este informativo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $this->data['informativo'] = $informativo;
        $this->data['categorias'] = $repo->getCategorias();
        // Departamentos alvo para notificação (para pré-selecionar no formulário)
        $this->data['notify_departments_ids'] = $repo->getNotifyDepartmentsIds((int)$id);
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['all_departments_for_notify'] = $deptRepo->getAllDepartmentsSelect();
        $this->data['department_select_locked'] = !UserAccessHelper::hasFullSystemAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        $pageElements = [
            'title_head' => 'Editar Informativo',
            'menu' => 'update-informativo',
            'buttonPermission' => ['UpdateInformativo'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/informativos/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('update_informativo', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro de validação CSRF!</div>';
            return;
        }

        $repoCheck = new InformativosRepository();
        $rowFresh = $repoCheck->getInformativoById($id);
        if (!$rowFresh) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }
        $uid = InformativosPermissionService::sessionUserId();
        $udept = InformativosPermissionService::sessionUserDepartmentId();
        if (!InformativosPermissionService::canManageRecord($rowFresh, $uid, $udept)) {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Você não tem permissão para editar este informativo.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $titulo = trim(TextEncodingHelper::decodeEntities((string)($_POST['titulo'] ?? '')));
        $conteudo = trim(TextEncodingHelper::decodeEntities((string)($_POST['conteudo'] ?? '')));
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $categoriaNome = trim(TextEncodingHelper::decodeEntities((string)($_POST['categoria'] ?? '')));
        $departmentId = InformativosPermissionService::resolveUpdateDepartmentId(
            $rowFresh,
            (int)($_POST['department_id'] ?? 0),
            $udept
        );
        $publishAt = trim($_POST['publish_at'] ?? '');
        $expireAt = trim($_POST['expire_at'] ?? '');
        $urgente = isset($_POST['urgente']) ? true : false;
        $requiresAck = isset($_POST['requires_ack']) ? true : false;
        $ativo = isset($_POST['ativo']) ? true : false;
        $notificar = isset($_POST['notificar']) ? true : false;
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

        // Gerar resumo (primeiras 150 letras)
        $resumo = substr(strip_tags($conteudo), 0, 150);
        if (strlen(strip_tags($conteudo)) > 150) {
            $resumo .= '...';
        }

        // Upload de nova imagem
        $imagem = $this->data['informativo']['imagem'];
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $novaImagem = $this->uploadFile($_FILES['imagem'], 'imagens', $imagem);
            if ($novaImagem) {
                $imagem = $novaImagem;
            }
        }

        // Upload de novo anexo
        $anexo = $this->data['informativo']['anexo'];
        if (isset($_FILES['anexo'])) {
            if ($_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
                $novoAnexo = $this->uploadFile($_FILES['anexo'], 'anexos', $anexo);
                if ($novoAnexo) {
                    $anexo = $novoAnexo;
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Não foi possível salvar o anexo. Verifique o tipo e o tamanho do arquivo (máx. 5MB) e tente novamente.</div>';
                    return;
                }
            } elseif ($_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Erro no upload do anexo. Código: ' . (int)$_FILES['anexo']['error'] . '</div>';
                return;
            } elseif (!empty($_FILES['anexo']['name']) && empty($_FILES['anexo']['tmp_name'])) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">O arquivo não foi recebido pelo servidor (tmp_name vazio). Verifique limites de upload do PHP (upload_max_filesize/post_max_size) e tente novamente.</div>';
                return;
            }
        }

        // Validações de datas
        $now = new \DateTime('now');
        $publishDt = null; $expireDt = null;
        if (!empty($publishAt)) { $publishDt = new \DateTime($publishAt); }
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

        if ($publishDt !== null && $publishDt > $now) {
            $ativo = false;
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
            'publish_at' => $publishDt ? $publishDt->format('Y-m-d H:i:s') : null,
            'expire_at' => $expireDt ? $expireDt->format('Y-m-d H:i:s') : null,
        ];

        $repo = new InformativosRepository();
        
        try {
            $success = $repo->updateInformativo($id, $data);
            
            if ($success) {
                // Atualizar departamentos alvo de notificação
                $repo->replaceNotifyDepartments($id, $notifyDepartments);

                $now = new \DateTime('now');
                if ($ativo && ($publishDt === null || $publishDt <= $now)) {
                    InformativoPublishNotifier::notifyPublished((int) $id);
                }

                // Se estiver marcado para notificar e ativo, disparar WhatsApp
                if ($notificar && $ativo) {
                    WhatsappNotificationService::notificarInformativoUrgente((int)$id);
                }

                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Informativo atualizado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
                exit;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar informativo!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar informativo: ' . $e->getMessage() . '</div>';
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
        // Limite alinhado ao php.ini (upload_max_filesize/post_max_size). Aqui usamos 20MB por segurança.
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