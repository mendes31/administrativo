<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\projects\ProjProjectsRepository;
use App\adms\Models\Repository\projects\ProjProjectStagesRepository;
use App\adms\Models\Repository\projects\ProjStagesRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateProject
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_project', $this->data['form']['csrf_token'])) {
            $this->update((int)$id);
            return;
        }

        $repo = new ProjProjectsRepository();
        $project = $repo->getOne((int)$id);
        if (!$project) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Projeto não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        if (empty($this->data['form'])) {
            $this->data['form'] = $project;
        }

        // Carregar etapas existentes do projeto (com depends_on_index para o formulário)
        $stagesRepo = new ProjProjectStagesRepository();
        $rawStages = $stagesRepo->getByProject((int)$id);
        $idToIndex = [];
        foreach ($rawStages as $i => $row) {
            $idToIndex[(int)$row['id']] = $i;
        }
        foreach ($rawStages as $i => $row) {
            $rawStages[$i]['depends_on_index'] = isset($row['depends_on_stage_id'], $idToIndex[(int)$row['depends_on_stage_id']])
                ? (string)$idToIndex[(int)$row['depends_on_stage_id']] : '';
        }
        $this->data['stages'] = $rawStages;

        $this->view();
    }

    private function view(): void
    {
        $usersRepo = new UsersRepository();
        $this->data['listUsers'] = $usersRepo->getAllUsersForSelect();

        $stagesCatalog = new ProjStagesRepository();
        $this->data['listStages'] = $stagesCatalog->getAllForSelect();

        $pageElements = [
            'title_head' => 'Editar Projeto',
            'menu' => 'list-projects',
            'buttonPermission' => ['ListProjects', 'UpdateProject'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/projects/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];

        if (empty($form['name'])) {
            $errors['name'] = 'Nome do projeto é obrigatório.';
        }

        if (empty($form['type']) || !in_array($form['type'], ['INTERNAL', 'EXTERNAL'], true)) {
            $form['type'] = 'INTERNAL';
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->view();
            return;
        }

        $repo = new ProjProjectsRepository();
        $updated = $repo->update($id, [
            'type' => $form['type'],
            'name' => trim($form['name']),
            'status' => $form['status'] ?? 'INICIADO',
            'start_date' => $form['start_date'] ?: null,
            'expected_end_date' => $form['expected_end_date'] ?: null,
            'end_date' => $form['end_date'] ?: null,
            'open_activities' => (int)($form['open_activities'] ?? 0),
            'percent_complete' => (float)($form['percent_complete'] ?? 0),
            'pn_id' => null,
            'pn_code' => $form['pn_code'] ?? null,
            'pn_name' => $form['pn_name'] ?? null,
            'contact_user_id' => !empty($form['contact_user_id']) ? (int)$form['contact_user_id'] : null,
            'owner_user_id' => !empty($form['owner_user_id']) ? (int)$form['owner_user_id'] : null,
            'description' => $form['description'] ?? null,
            'active' => !empty($form['active']) ? 1 : 0,
        ]);

        if ($updated) {
            // Salvar etapas do projeto (se vieram no formulário)
            $this->saveProjectStages($id, $form);

            GenerateLog::generateLog('info', 'Projeto atualizado', ['id' => $id]);
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Projeto atualizado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar projeto.</div>";
        $this->view();
    }

    /**
     * Monta as linhas de etapas a partir do formulário e persiste na base.
     *
     * Por enquanto lê apenas campos básicos; os nomes dos campos serão usados
     * na etapa seguinte quando criarmos a aba visual de "Etapas".
     */
    private function saveProjectStages(int $projectId, array $form): void
    {
        // Se ainda não existem campos de etapas no formulário, não faz nada.
        if (empty($form['stage_name'])) {
            return;
        }

        $names      = $form['stage_name'];
        $stageIds   = $form['stage_id'] ?? [];
        $sequences  = $form['stage_sequence'] ?? [];
        $starts     = $form['stage_start_date'] ?? [];
        $expected   = $form['stage_expected_end_date'] ?? [];
        $ends       = $form['stage_end_date'] ?? [];
        $activities = $form['stage_activity'] ?? [];
        $descs      = $form['stage_description'] ?? [];
        $responsible = $form['stage_responsible_user_id'] ?? [];
        $dependsIndex = $form['stage_depends_on_index'] ?? [];
        $completed  = $form['stage_completed'] ?? [];

        $stageNamesById = $this->getStageNamesById(array_filter(array_map('intval', $stageIds)));

        $lines = [];
        foreach ($names as $idx => $name) {
            $name = trim((string)$name);
            $stageId = !empty($stageIds[$idx]) ? (int)$stageIds[$idx] : null;
            if ($name === '' && $stageId) {
                $name = $stageNamesById[$stageId] ?? '';
            }
            if ($name === '') {
                continue;
            }

            $depIdx = isset($dependsIndex[$idx]) && $dependsIndex[$idx] !== '' && $dependsIndex[$idx] !== null
                ? (int)$dependsIndex[$idx] : null;

            $lines[] = [
                'stage_id'             => $stageId,
                'name'                 => $name,
                'sequence'             => (int)($sequences[$idx] ?? ($idx + 1)),
                'start_date'           => !empty($starts[$idx]) ? $starts[$idx] : null,
                'expected_end_date'    => !empty($expected[$idx]) ? $expected[$idx] : null,
                'end_date'             => !empty($ends[$idx]) ? $ends[$idx] : null,
                'activity'             => isset($activities[$idx]) ? trim((string)$activities[$idx]) : null,
                'description'          => isset($descs[$idx]) ? trim((string)$descs[$idx]) : null,
                'responsible_user_id'  => !empty($responsible[$idx]) ? (int)$responsible[$idx] : null,
                'depends_on_index'     => $depIdx,
                'completed'            => isset($completed[$idx]) ? 1 : 0,
                'is_cost_stage'        => 0,
                'status'               => 'NAO_INICIADO',
                'percent_complete'     => 0,
            ];
        }

        if (!$lines) {
            return;
        }

        $stagesRepo = new ProjProjectStagesRepository();
        $stagesRepo->replaceForProject($projectId, $lines);
    }

    /**
     * Retorna mapa id => name das etapas do catálogo para os IDs informados.
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    /**
     * @param int[] $ids
     * @return array<int, string>
     */
    private function getStageNamesById(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $repo = new ProjStagesRepository();
        return $repo->getNamesByIds($ids);
    }
}

