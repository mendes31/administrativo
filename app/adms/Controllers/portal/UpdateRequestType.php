<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Models\Repository\RequestTypeStagesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar tipo de solicitação + fluxo de aprovação por etapas.
 */
class UpdateRequestType
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int) $id) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        $repository = new RequestTypesRepository();
        $this->data['requestType'] = $repository->getById((int) $id);

        if (!$this->data['requestType']) {
            $_SESSION['error'] = 'Tipo de solicitação não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-request-types");
            return;
        }

        $stagesRepo = new RequestTypeStagesRepository();
        $stagesRepo->ensureStagesForType(
            (int) $id,
            !empty($this->data['requestType']['requires_manager_approval']),
            !isset($this->data['requestType']['requires_hr_approval'])
                || !empty($this->data['requestType']['requires_hr_approval'])
        );
        $this->data['stages'] = $stagesRepo->listByTypeId((int) $id);

        $levelsRepo = new \App\adms\Models\Repository\UsersAccessLevelsRepository();
        $this->data['accessLevels'] = $levelsRepo->getAllAccessLevels() ?: [];
        $this->data['skipRequesterLevelIds'] = $repository->getSkipImmediateRequesterLevelIds($this->data['requestType']);
        $this->data['skipSupervisorLevelIds'] = $repository->getSkipImmediateSupervisorLevelIds($this->data['requestType']);
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int) $id);
            $this->data['requestType'] = $repository->getById((int) $id) ?: $this->data['requestType'];
            $this->data['stages'] = $stagesRepo->listByTypeId((int) $id);
            $this->data['skipRequesterLevelIds'] = $repository->getSkipImmediateRequesterLevelIds($this->data['requestType']);
            $this->data['skipSupervisorLevelIds'] = $repository->getSkipImmediateSupervisorLevelIds($this->data['requestType']);
        }

        $pageElements = [
            'title_head' => 'Editar Tipo de Solicitação',
            'menu' => 'list-request-types',
            'buttonPermission' => [
                'ListRequestTypes',
                'ViewRequestType',
                'UpdateRequestType',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/update_request_type', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_request_type', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $stagesInput = $this->parseStagesFromPost($_POST['stages'] ?? []);
        if ($stagesInput === []) {
            $_SESSION['error'] = 'Monte ao menos uma etapa no fluxo (gestor, RH ou usuário específico).';
            return;
        }

        $stagesRepo = new RequestTypeStagesRepository();
        $flags = $stagesRepo->deriveFlagsFromStages($stagesInput);

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requires_manager_approval' => $flags['requires_manager'],
            'requires_hr_approval' => $flags['requires_hr'],
            'requires_dates' => isset($_POST['requires_dates']) && $_POST['requires_dates'] === '1',
            'requires_days' => isset($_POST['requires_days']) && $_POST['requires_days'] === '1',
            'requires_amount' => isset($_POST['requires_amount']) && $_POST['requires_amount'] === '1',
            'icon' => trim($_POST['icon'] ?? ''),
            'color' => $_POST['color'] ?? 'primary',
            'status' => isset($_POST['status']) && $_POST['status'] === '1',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'skip_immediate_requester_level_ids' => $_POST['skip_immediate_requester_level_ids'] ?? [],
            'skip_immediate_supervisor_level_ids' => $_POST['skip_immediate_supervisor_level_ids'] ?? [],
        ];

        if ($data['name'] === '') {
            $_SESSION['error'] = 'Nome é obrigatório.';
            return;
        }

        $repository = new RequestTypesRepository();

        if (!$repository->update($id, $data)) {
            $_SESSION['error'] = 'Erro ao atualizar tipo de solicitação. Tente novamente.';
            return;
        }

        if (!$stagesRepo->replaceStagesForType($id, $stagesInput)) {
            $_SESSION['error'] = 'Tipo salvo, mas falhou ao gravar as etapas. Verifique usuários específicos e tente novamente.';
            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Tipo e fluxo de etapas atualizados com sucesso!</div>';
        GenerateLog::generateLog('info', 'Tipo de solicitação atualizado.', ['id' => $id, 'stages' => count($stagesInput)]);
        header('Location: ' . $_ENV['URL_ADM'] . 'update-request-type/' . $id);
        exit;
    }

    /**
     * @param mixed $raw
     * @return list<array{approver_kind: string, stage_label?: string, fixed_user_id?: int|null, escalate_after_hours?: int, escalate_policy?: string, max_escalation_levels?: int}>
     */
    private function parseStagesFromPost(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kind = (string) ($row['approver_kind'] ?? '');
            if (!in_array($kind, RequestTypeStagesRepository::KINDS, true)) {
                continue;
            }
            $item = [
                'approver_kind' => $kind,
                'stage_label' => trim((string) ($row['stage_label'] ?? '')),
                'fixed_user_id' => isset($row['fixed_user_id']) ? (int) $row['fixed_user_id'] : null,
                'hierarchy_level' => max(1, min(10, (int) ($row['hierarchy_level'] ?? 1))),
                'escalate_after_hours' => (int) ($row['escalate_after_hours'] ?? 0),
                'escalate_policy' => (string) ($row['escalate_policy'] ?? 'none'),
                'max_escalation_levels' => (int) ($row['max_escalation_levels'] ?? 0),
            ];
            if ($kind !== 'immediate') {
                $item['hierarchy_level'] = 1;
            }
            if ($kind === 'immediate' && $item['escalate_after_hours'] <= 0 && ($row['escalate_after_hours'] ?? '') === '') {
                $item['escalate_after_hours'] = 72;
            }
            if ($kind === 'immediate' && $item['escalate_policy'] === 'none' && !isset($row['escalate_policy'])) {
                $item['escalate_policy'] = 'next_level';
            }
            if ($kind === 'immediate' && $item['max_escalation_levels'] <= 0 && !isset($row['max_escalation_levels'])) {
                $item['max_escalation_levels'] = 1;
            }
            $out[] = $item;
        }

        return $out;
    }
}
