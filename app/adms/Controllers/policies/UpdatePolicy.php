<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogAlteracaoService;
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
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);
        if (!$policy) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política não encontrada.</div>';
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
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
            if (!$publishDt && $expireDt <= $now) {
                $_SESSION['msg'] = '<div class="alert alert-danger">A expiração deve ser maior que agora quando não há publicação futura.</div>';
                $this->prepareViewData($oldPolicy, $repo);
                return;
            }
        }

        // Mantém imagem/anexo atuais (edição simples)
        $imagem = $oldPolicy['imagem'] ?? null;
        $anexo  = $oldPolicy['anexo'] ?? null;

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
                $repo->replaceNotifyDepartments($id, $notifyDepartments);

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
                header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
                exit;
            }

            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao atualizar política.</div>';
        } catch (\Throwable $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao atualizar política: ' . $e->getMessage() . '</div>';
        }

        $this->prepareViewData($oldPolicy, $repo);
    }
}

