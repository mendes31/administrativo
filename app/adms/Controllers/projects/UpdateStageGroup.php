<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\projects\ProjStageGroupsRepository;
use App\adms\Models\Repository\projects\ProjStagesRepository;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Views\Services\LoadViewService;

class UpdateStageGroup
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_stage_group', $this->data['form']['csrf_token'])) {
            $this->update((int)$id);
            return;
        }

        $repo = new ProjStageGroupsRepository();
        $group = $repo->getOne((int)$id);
        if (!$group) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Grupo de etapas não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        if (empty($this->data['form'])) {
            $this->data['form'] = $group;
        }

        // Carregar etapas do grupo e catálogo de etapas para o formulário
        $this->data['items'] = $repo->getItemsByGroup((int)$id);

        $stagesRepo = new ProjStagesRepository();
        $this->data['listStages'] = $stagesRepo->getAllForSelect();

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head'       => 'Editar Grupo de Etapas',
            'menu'             => 'list-stage-groups',
            'buttonPermission' => ['ListStageGroups', 'UpdateStageGroup'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge(
            $this->data ?? [],
            $pageLayoutService->configurePageElements($pageElements)
        );

        $loadView = new LoadViewService('adms/Views/projects/stage_groups/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];

        if (empty($form['name'])) {
            $errors['name'] = 'Nome do grupo é obrigatório.';
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $repo = new ProjStageGroupsRepository();
        $antes = $repo->getOne($id);
        if (!$antes) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Grupo de etapas não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        $payload = [
            'name'        => trim($form['name']),
            'description' => $form['description'] ?? null,
            'active'      => !empty($form['active']) ? 1 : 0,
        ];

        $updated = $repo->update($id, $payload);

        if ($updated) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 0);
            LogAlteracaoService::registrarAlteracao(
                'proj_stage_groups',
                (int)$id,
                $usuarioId,
                'update',
                $antes,
                array_merge($antes, $payload)
            );

            // Salvar as etapas do grupo (itens)
            $this->saveGroupItems($id, $form, $usuarioId);

            GenerateLog::generateLog('info', 'Grupo de etapas de projeto atualizado', ['id' => $id]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Grupo de etapas atualizado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-stage-groups');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar grupo de etapas.</div>";
        $this->view();
    }

    /**
     * Salva as etapas vinculadas a um grupo de etapas.
     *
     * @param int   $groupId
     * @param array $form
     * @param int   $usuarioId
     * @return void
     */
    private function saveGroupItems(int $groupId, array $form, int $usuarioId): void
    {
        $stageIds  = $form['group_item_stage_id'] ?? [];
        $sequences = $form['group_item_sequence'] ?? [];

        $repo = new ProjStageGroupsRepository();

        $antesItems = $repo->getItemsByGroup($groupId);

        $lines = [];
        foreach ($stageIds as $idx => $stageId) {
            $stageId = (int)$stageId;
            if ($stageId <= 0) {
                continue;
            }

            $sequence = (int)($sequences[$idx] ?? ($idx + 1));
            if ($sequence <= 0) {
                $sequence = $idx + 1;
            }

            $lines[] = [
                'stage_id' => $stageId,
                'sequence' => $sequence,
            ];
        }

        $repo->replaceItemsForGroup($groupId, $lines);

        $depoisItems = $repo->getItemsByGroup($groupId);

        LogAlteracaoService::registrarAlteracao(
            'proj_stage_group_items',
            $groupId,
            $usuarioId,
            'update',
            ['items_antes' => json_encode($antesItems)],
            ['items_depois' => json_encode($depoisItems)]
        );
    }
}

