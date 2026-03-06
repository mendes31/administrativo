<?php

namespace App\adms\Controllers\projects;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\UsersAccessLevelsRepository;
use App\adms\Models\Repository\projects\ProjCommentsRepository;
use App\adms\Models\Repository\projects\ProjProjectsRepository;
use App\adms\Models\Repository\projects\ProjProjectStagesRepository;
use App\adms\Models\Repository\projects\ProjStagesRepository;
use App\adms\Models\Repository\projects\ProjStageGroupsRepository;
use App\adms\Models\Services\WorkdayCalendarService;
use App\adms\Models\Services\WhatsappNotificationService;
use App\adms\Views\Services\LoadViewService;

class UpdateProject
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        // Marcar notificação como lida quando o usuário acessa o projeto pelo link da notificação
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && isset($_GET['mark_notification']) && is_numeric($_GET['mark_notification'])) {
            $notifRepo = new NotificationsRepository();
            $notifRepo->markAsRead((int)$_GET['mark_notification'], $userId);
        }

        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        // Controlar qual aba deve permanecer ativa
        $this->data['active_tab'] = $this->data['form']['active_tab']
            ?? ($_GET['tab'] ?? 'dados-gerais');

        // POST: adicionar comentário (formulário separado da aba Comentários)
        if (!empty($this->data['form']['add_comment']) && (int)$id > 0) {
            $csrfComment = $_POST['csrf_token_comment'] ?? '';
            if (CSRFHelper::validateCSRFToken('form_add_comment', $csrfComment)) {
                $repoProj = new ProjProjectsRepository();
                $projectForComment = $repoProj->getOne((int)$id);
                if ($projectForComment) {
                    $body = trim((string)($this->data['form']['comment_body'] ?? ''));
                    if ($body !== '') {
                        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
                        $stageId = !empty($this->data['form']['comment_stage_id'])
                            ? (int)$this->data['form']['comment_stage_id'] : null;

                        // Menções vindas explicitamente do multiselect
                        $mentionUserIds = isset($this->data['form']['mention_user_ids']) && is_array($this->data['form']['mention_user_ids'])
                            ? array_map('intval', $this->data['form']['mention_user_ids']) : [];

                        // Menções escritas no corpo do comentário com @Nome do usuário
                        if ($body !== '') {
                            $usersRepoForMentions = new UsersRepository();
                            $allUsersForMentions = $usersRepoForMentions->getAllUsersForSelect();
                            foreach ($allUsersForMentions as $u) {
                                $userName = trim((string)($u['name'] ?? ''));
                                if ($userName === '') {
                                    continue;
                                }
                                // Procura por @Nome (case-insensitive)
                                if (mb_stripos($body, '@' . $userName) !== false) {
                                    $mentionUserIds[] = (int)$u['id'];
                                }
                            }
                            $mentionUserIds = array_unique($mentionUserIds);
                        }

                        $commentsRepo = new ProjCommentsRepository();
                        $commentId = $commentsRepo->create((int)$id, $stageId, $currentUserId, $body, $mentionUserIds);
                        if ($commentId) {
                            $projectName = $projectForComment['name'] ?? 'Projeto';
                            $baseUrl = $_ENV['URL_ADM'] . 'update-project/' . (int)$id . '?tab=comentarios';
                            $notifRepo = new NotificationsRepository();
                            $usersRepo = new UsersRepository();
                            $authorName = 'Alguém';
                            if ($currentUserId > 0) {
                                $author = $usersRepo->getUser($currentUserId);
                                if (is_array($author) && !empty($author['name'])) {
                                    $authorName = $author['name'];
                                }
                            }
                            foreach ($mentionUserIds as $mentionedId) {
                                if ($mentionedId === $currentUserId) {
                                    continue;
                                }
                                $notifId = $notifRepo->create([
                                    'user_id' => $mentionedId,
                                    'type' => 'comentario_mencao',
                                    'title' => 'Menção em comentário',
                                    'message' => $authorName . ' mencionou você em um comentário no projeto "' . $projectName . '".',
                                    'link_url' => $baseUrl,
                                    'entity_type' => 'proj_comment',
                                    'entity_id' => $commentId,
                                ]);
                                if ($notifId) {
                                    $notifRepo->updateLinkUrl($notifId, $baseUrl . '&mark_notification=' . $notifId);
                                }
                            }
                            $_SESSION['msg'] = 'Comentário adicionado.';
                            $_SESSION['msg_type'] = 'success';
                        } else {
                            $_SESSION['msg'] = 'Erro ao salvar o comentário.';
                            $_SESSION['msg_type'] = 'danger';
                        }
                    } else {
                        $_SESSION['msg'] = 'Escreva o comentário.';
                        $_SESSION['msg_type'] = 'warning';
                    }
                }
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'update-project/' . (int)$id . '?tab=comentarios');
            return;
        }

        if (!empty($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_project', $this->data['form']['csrf_token'])) {
            // Se o usuário clicou em "Carregar grupo de etapas", apenas aplica o template e não salva o projeto ainda
            if (!empty($this->data['form']['apply_stage_group'])) {
                $this->applyStageGroup((int)$id);
                return;
            }

            $this->update((int)$id);
            return;
        }

        $repo = new ProjProjectsRepository();
        $project = $repo->getOne((int)$id);
        if (!$project) {
            $_SESSION['msg'] = 'Projeto não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-projects');
            return;
        }

        // Determinar se o usuário logado é gestor do projeto
        $isProjectManager = false;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if ($currentUserId > 0) {
            // Super administrador (nível de acesso 1) sempre é gestor
            $accessRepo = new UsersAccessLevelsRepository();
            $levels = $accessRepo->getUserAccessLevelArray($currentUserId) ?: [];
            if (in_array(1, $levels, true)) {
                $isProjectManager = true;
            }

            // Dono do projeto também é gestor
            if (!$isProjectManager && (int)($project['owner_user_id'] ?? 0) === $currentUserId) {
                $isProjectManager = true;
            }
        }
        $this->data['isProjectManager'] = $isProjectManager;

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

        // Comentários do projeto (aba Comentários)
        $commentsRepo = new ProjCommentsRepository();
        $commentStageFilter = isset($_GET['comment_stage_filter']) ? (int)$_GET['comment_stage_filter'] : null;
        if ($commentStageFilter === 0) {
            $commentStageFilter = null;
        }
        $this->data['projectComments'] = $commentsRepo->listByProject((int)$id, $commentStageFilter);
        $this->data['comment_stage_filter'] = $_GET['comment_stage_filter'] ?? '';

        $this->view();
    }

    private function view(): void
    {
        $usersRepo = new UsersRepository();
        $this->data['listUsers'] = $usersRepo->getAllUsersForSelect();

        $stagesCatalog = new ProjStagesRepository();
        $this->data['listStages'] = $stagesCatalog->getAllForSelect();

        // Catálogo de grupos de etapas (para aplicar template de etapas no projeto)
        $groupsRepo = new ProjStageGroupsRepository();
        $this->data['listStageGroups'] = $groupsRepo->getAllForSelect();

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

        // Garantir que a aba ativa seja preservada mesmo em erro
        $this->data['active_tab'] = $form['active_tab'] ?? 'dados-gerais';

        if (empty($form['name'])) {
            $errors['name'] = 'Nome do projeto é obrigatório.';
        }

        if (empty($form['type']) || !in_array($form['type'], ['INTERNAL', 'EXTERNAL'], true)) {
            $form['type'] = 'INTERNAL';
        }

        // Validação de dependências entre etapas (somente se houver etapas no formulário)
        $depError = $this->validateStagesDependencies($form);
        if ($depError !== '') {
            // Apenas registra a mensagem e volta para o estado persistido,
            // sem tentar reconstruir as etapas a partir do POST
            $_SESSION['msg'] = $depError;
            $_SESSION['msg_type'] = 'danger';
            $tab = 'etapas';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-project/' . $id . '?tab=' . $tab);
            return;
        }

        if ($errors) {
            $this->data['errors'] = $errors;
            $_SESSION['msg'] = 'Verifique os campos obrigatórios.';
            $_SESSION['msg_type'] = 'danger';
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
            $_SESSION['msg'] = 'Projeto atualizado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            // Permanecer na tela de edição do projeto na mesma aba
            $tab = urlencode($form['active_tab'] ?? 'dados-gerais');
            header('Location: ' . $_ENV['URL_ADM'] . 'update-project/' . $id . '?tab=' . $tab);
            return;
        }

        $_SESSION['msg'] = 'Erro ao atualizar projeto.';
        $_SESSION['msg_type'] = 'danger';
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
        // Se não existem campos de etapas no formulário:
        // - Se o usuário estava na aba "Etapas", interpretamos como intenção de remover todas as etapas do projeto.
        // - Caso contrário (salvo pela aba Dados gerais), não alteramos as etapas existentes.
        if (empty($form['stage_name'])) {
            if (($form['active_tab'] ?? '') === 'etapas') {
                $stagesRepo = new ProjProjectStagesRepository();
                $stagesRepo->replaceForProject($projectId, []);
            }
            return;
        }

        $names       = $form['stage_name'];
        $stageIds    = $form['stage_id'] ?? [];
        $sequences   = $form['stage_sequence'] ?? [];
        $starts      = $form['stage_start_date'] ?? [];
        $expected    = $form['stage_expected_end_date'] ?? [];
        $ends        = $form['stage_end_date'] ?? [];
        $activities  = $form['stage_activity'] ?? [];
        $descs       = $form['stage_description'] ?? [];
        $responsible = $form['stage_responsible_user_id'] ?? [];
        $origResp    = $form['stage_original_responsible_user_id'] ?? [];
        $dependsIndex = $form['stage_depends_on_index'] ?? [];
        $completed   = $form['stage_completed'] ?? [];
        $statuses    = $form['stage_status'] ?? [];

        $stageIdsInt = array_filter(array_map('intval', $stageIds));
        $stageNamesById = $this->getStageNamesById($stageIdsInt);

        // Mapa id_etapa_catálogo => dias previstos (úteis)
        $stageDurationsById = [];
        if ($stageIdsInt) {
            $stagesRepo = new ProjStagesRepository();
            $stageDurationsById = $stagesRepo->getEstimatedWorkdaysByIds($stageIdsInt);
        }

        $lines = [];
        $statusByIndex = [];
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

            // Status da etapa (work breakdown / tarefa)
            $rawStatus = strtoupper(trim((string)($statuses[$idx] ?? 'NAO_INICIADO')));
            $allowedStatuses = [
                'NAO_INICIADO',
                'EM_ANDAMENTO',
                'EM_VALIDACAO',
                'AGUARDANDO_APROVACAO',
                'BLOQUEADO',
                'CONCLUIDO',
                'CANCELADO',
            ];
            if (!in_array($rawStatus, $allowedStatuses, true)) {
                $rawStatus = 'NAO_INICIADO';
            }

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
                'original_responsible_user_id' => (isset($origResp[$idx]) && $origResp[$idx] !== '')
                    ? (int)$origResp[$idx] : null,
                'depends_on_index'     => $depIdx,
                'completed'            => isset($completed[$idx]) ? 1 : 0,
                'is_cost_stage'        => 0,
                'status'               => $rawStatus,
                'percent_complete'     => 0,
            ];

            $statusByIndex[$idx] = $rawStatus;
        }

        if (!$lines) {
            return;
        }

        // Agendamento automático de datas usando dias úteis + calendário
        $calendar = new WorkdayCalendarService();
        $baseDateStr = $form['start_date'] ?? date('Y-m-d');
        $baseDate = \DateTimeImmutable::createFromFormat('Y-m-d', $baseDateStr) ?: new \DateTimeImmutable();
        $currentStart = $baseDate;

        // Ordena por sequence para agendamento encadeado
        usort($lines, static function (array $a, array $b): int {
            return ($a['sequence'] <=> $b['sequence']);
        });

        foreach ($lines as &$line) {
            $hasDates = !empty($line['start_date']) || !empty($line['expected_end_date']);
            $stageIdForDuration = $line['stage_id'] ?? null;
            $duration = $stageIdForDuration && isset($stageDurationsById[$stageIdForDuration])
                ? (int)$stageDurationsById[$stageIdForDuration]
                : 0;

            // Só agenda automaticamente quando não houver datas preenchidas e existir prazo configurado
            if (!$hasDates && $duration > 0) {
                // Garante que o início seja um dia útil
                while (!$calendar->isWorkday($currentStart)) {
                    $currentStart = $currentStart->modify('+1 day');
                }

                $line['start_date'] = $currentStart->format('Y-m-d');

                // expected_end = start + (duration - 1) dias úteis
                $expectedEnd = $duration > 1
                    ? $calendar->addWorkdays($currentStart, $duration - 1)
                    : $currentStart;

                $line['expected_end_date'] = $expectedEnd->format('Y-m-d');

                // Próxima etapa começa no próximo dia útil após o término desta
                $currentStart = $calendar->nextWorkday($expectedEnd);
            } elseif (!empty($line['expected_end_date'])) {
                // Se já existe previsão de término informada, usa como base para a próxima etapa
                $nextBase = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$line['expected_end_date']);
                if ($nextBase instanceof \DateTimeImmutable) {
                    $currentStart = $calendar->nextWorkday($nextBase);
                }
            }
        }
        unset($line);

        // Para etapas sem dependência e ainda sem Data Início,
        // define a Data Início como a data em que a etapa foi adicionada (próximo dia útil "hoje").
        $today = new \DateTimeImmutable();
        while (!$calendar->isWorkday($today)) {
            $today = $today->modify('+1 day');
        }
        foreach ($lines as &$line) {
            $depIdxForStart = $line['depends_on_index'] ?? null;
            if (empty($line['start_date']) && ($depIdxForStart === null || $depIdxForStart === '')) {
                $line['start_date'] = $today->format('Y-m-d');
            }
        }
        unset($line);

        // Auto-iniciar etapas dependentes:
        // Se uma etapa depende de outra que está CONCLUIDO e ela mesma está NAO_INICIADO,
        // alteramos automaticamente para EM_ANDAMENTO.
        foreach ($lines as $idx => &$line) {
            $depIdx = $line['depends_on_index'] ?? null;
            if ($depIdx === null) {
                continue;
            }

            $myStatus  = $line['status'] ?? 'NAO_INICIADO';
            $depStatus = $statusByIndex[$depIdx] ?? 'NAO_INICIADO';

            if ($myStatus === 'NAO_INICIADO' && $depStatus === 'CONCLUIDO') {
                $line['status'] = 'EM_ANDAMENTO';
                $statusByIndex[$idx] = 'EM_ANDAMENTO';
            }
        }
        unset($line);

        // Carregar etapas antigas para detectar alterações campo a campo
        $projectStagesRepo = new ProjProjectStagesRepository();
        $oldStages = $projectStagesRepo->getByProject($projectId);
        $oldStages = array_values($oldStages); // garantir índices sequenciais

        // Persistir novas etapas
        $projectStagesRepo->replaceForProject($projectId, $lines);

        // Notificar por WhatsApp e notificação interna:
        // - novos responsáveis
        // - responsáveis trocados
        // - responsáveis removidos
        // - responsável atual quando qualquer campo da etapa for modificado
        $projectsRepo = new ProjProjectsRepository();
        $project = $projectsRepo->getOne($projectId);
        $projectName = $project['name'] ?? 'Projeto';
        $urlBase = rtrim($_ENV['URL_ADM'] ?? '', '/');
        $notifRepo = new NotificationsRepository();
        foreach ($lines as $idx => $line) {
            $newResp = $line['responsible_user_id'] ?? null;
            $newRespInt = !empty($newResp) ? (int)$newResp : null;

            $oldStage = $oldStages[$idx] ?? null;
            $oldRespInt = $oldStage && !empty($oldStage['responsible_user_id'])
                ? (int)$oldStage['responsible_user_id']
                : null;

            $responsibleChanged = $newRespInt !== $oldRespInt;

            // Detectar se algum outro campo relevante da etapa mudou
            $contentChanged = false;
            if ($oldStage) {
                $oldNorm = [
                    'stage_id'          => (int)($oldStage['stage_id'] ?? 0),
                    'name'              => (string)($oldStage['name'] ?? ''),
                    'activity'          => (string)($oldStage['activity'] ?? ''),
                    'description'       => (string)($oldStage['description'] ?? ''),
                    'sequence'          => (int)($oldStage['sequence'] ?? 0),
                    'status'            => (string)($oldStage['status'] ?? ''),
                    'start_date'        => (string)($oldStage['start_date'] ?? ''),
                    'expected_end_date' => (string)($oldStage['expected_end_date'] ?? ''),
                    'end_date'          => (string)($oldStage['end_date'] ?? ''),
                    'completed'         => !empty($oldStage['completed']) ? 1 : 0,
                ];
                $newNorm = [
                    'stage_id'          => (int)($line['stage_id'] ?? 0),
                    'name'              => (string)($line['name'] ?? ''),
                    'activity'          => (string)($line['activity'] ?? ''),
                    'description'       => (string)($line['description'] ?? ''),
                    'sequence'          => (int)($line['sequence'] ?? 0),
                    'status'            => (string)($line['status'] ?? ''),
                    'start_date'        => (string)($line['start_date'] ?? ''),
                    'expected_end_date' => (string)($line['expected_end_date'] ?? ''),
                    'end_date'          => (string)($line['end_date'] ?? ''),
                    'completed'         => !empty($line['completed']) ? 1 : 0,
                ];
                $contentChanged = $oldNorm !== $newNorm;
            } else {
                // Etapa nova: consideramos como "alterada" para fins de notificação do novo responsável
                $contentChanged = true;
            }

            // 1) Novo responsável / troca de responsável: WhatsApp + notificação
            if ($newRespInt && $responsibleChanged) {
                $userId = $newRespInt;
                WhatsappNotificationService::notificarEtapaProjetoAtribuida(
                    $projectId,
                    $projectName,
                    $line['name'] ?? 'Etapa',
                    $userId
                );
                $notifRepo->create([
                    'user_id' => $userId,
                    'type' => 'projeto_etapa',
                    'title' => 'Etapa atribuída: ' . ($line['name'] ?? 'Etapa'),
                    'message' => 'Você foi atribuído(a) à etapa "' . ($line['name'] ?? 'Etapa') . '" do projeto "' . $projectName . '".',
                    'link_url' => $urlBase . '/update-project/' . $projectId,
                    'entity_type' => 'projeto',
                    'entity_id' => $projectId,
                ]);
            }

            // 2) Responsável removido da etapa
            if ($oldRespInt && (!$newRespInt || $responsibleChanged)) {
                $notifRepo->create([
                    'user_id' => $oldRespInt,
                    'type' => 'projeto_etapa_removida',
                    'title' => 'Removido da etapa: ' . ($line['name'] ?? 'Etapa'),
                    'message' => 'Você não é mais responsável pela etapa "' . ($line['name'] ?? 'Etapa') . '" do projeto "' . $projectName . '".',
                    'link_url' => $urlBase . '/update-project/' . $projectId,
                    'entity_type' => 'projeto',
                    'entity_id' => $projectId,
                ]);
            }

            // 3) Mesmo responsável, mas qualquer campo relevante da etapa foi modificado:
            //    notificação interna de atualização (sem WhatsApp extra).
            if ($newRespInt && !$responsibleChanged && $contentChanged) {
                $notifRepo->create([
                    'user_id' => $newRespInt,
                    'type' => 'projeto_etapa_atualizada',
                    'title' => 'Etapa atualizada: ' . ($line['name'] ?? 'Etapa'),
                    'message' => 'Uma etapa da qual você é responsável foi atualizada no projeto "' . $projectName . '".',
                    'link_url' => $urlBase . '/update-project/' . $projectId,
                    'entity_type' => 'projeto',
                    'entity_id' => $projectId,
                ]);
            }
        }
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

    /**
     * Constrói o array de etapas esperado pela view a partir dos campos do formulário.
     *
     * Usado principalmente quando há erro de validação para manter o que o usuário digitou.
     *
     * @param array $form
     * @return array<int, array<string, mixed>>
     */
    private function buildStagesFromForm(array $form): array
    {
        if (empty($form['stage_name'])) {
            return [];
        }

        $names        = $form['stage_name'];
        $stageIds     = $form['stage_id'] ?? [];
        $sequences    = $form['stage_sequence'] ?? [];
        $starts       = $form['stage_start_date'] ?? [];
        $expected     = $form['stage_expected_end_date'] ?? [];
        $ends         = $form['stage_end_date'] ?? [];
        $activities   = $form['stage_activity'] ?? [];
        $descs        = $form['stage_description'] ?? [];
        $responsible  = $form['stage_responsible_user_id'] ?? [];
        $dependsIndex = $form['stage_depends_on_index'] ?? [];
        $completed    = $form['stage_completed'] ?? [];
        $statuses     = $form['stage_status'] ?? [];

        $stageNamesById = $this->getStageNamesById(array_filter(array_map('intval', $stageIds)));

        $stages = [];
        foreach ($names as $idx => $name) {
            $name = trim((string)$name);
            $stageId = !empty($stageIds[$idx]) ? (int)$stageIds[$idx] : null;
            if ($name === '' && $stageId) {
                $name = $stageNamesById[$stageId] ?? '';
            }
            // linha completamente vazia: ignora
            if ($name === '' && !$stageId) {
                continue;
            }

            $sequence = (int)($sequences[$idx] ?? ($idx + 1));
            if ($sequence <= 0) {
                $sequence = $idx + 1;
            }

            $statusRaw = strtoupper(trim((string)($statuses[$idx] ?? 'NAO_INICIADO')));
            $allowedStatuses = [
                'NAO_INICIADO',
                'EM_ANDAMENTO',
                'EM_VALIDACAO',
                'AGUARDANDO_APROVACAO',
                'BLOQUEADO',
                'CONCLUIDO',
                'CANCELADO',
            ];
            if (!in_array($statusRaw, $allowedStatuses, true)) {
                $statusRaw = 'NAO_INICIADO';
            }

            $stages[] = [
                'id'                  => null,
                'project_id'          => (int)($form['id'] ?? 0),
                'stage_id'            => $stageId,
                'name'                => $name,
                'activity'            => isset($activities[$idx]) ? trim((string)$activities[$idx]) : null,
                'description'         => isset($descs[$idx]) ? trim((string)$descs[$idx]) : null,
                'sequence'            => $sequence,
                'is_cost_stage'       => 0,
                'status'              => $statusRaw,
                'percent_complete'    => 0,
                'start_date'          => !empty($starts[$idx]) ? $starts[$idx] : null,
                'expected_end_date'   => !empty($expected[$idx]) ? $expected[$idx] : null,
                'end_date'            => !empty($ends[$idx]) ? $ends[$idx] : null,
                'completed'           => isset($completed[$idx]) ? 1 : 0,
                'responsible_user_id' => !empty($responsible[$idx]) ? (int)$responsible[$idx] : null,
                'depends_on_stage_id' => null,
                'depends_on_index'    => isset($dependsIndex[$idx]) && $dependsIndex[$idx] !== '' ? (int)$dependsIndex[$idx] : '',
                'responsible_name'    => null,
            ];
        }

        return $stages;
    }

    /**
     * Valida se os status das etapas respeitam as dependências configuradas.
     *
     * Regras:
     *  - Se NÃO houver dependência, a etapa é livre (pode ser executada fora de sequência).
     *  - Se houver dependência (campo Dependência preenchido):
     *      - A etapa NÃO pode ficar em EM_ANDAMENTO / EM_VALIDACAO / AGUARDANDO_APROVACAO /
     *        BLOQUEADO / CONCLUIDO enquanto a etapa da qual depende não estiver CONCLUIDO.
     *
     * Retorna string vazia se estiver tudo OK ou mensagem de erro se houver problema.
     */
    private function validateStagesDependencies(array $form): string
    {
        if (empty($form['stage_name'])) {
            return '';
        }

        $names         = $form['stage_name'];
        $dependsIndex  = $form['stage_depends_on_index'] ?? [];
        $statusesRaw   = $form['stage_status'] ?? [];
        $completedRaw  = $form['stage_completed'] ?? [];

        $allowedStatuses = [
            'NAO_INICIADO',
            'EM_ANDAMENTO',
            'EM_VALIDACAO',
            'AGUARDANDO_APROVACAO',
            'BLOQUEADO',
            'CONCLUIDO',
            'CANCELADO',
        ];

        // Primeiro, normaliza os status por índice de linha
        $statusByIndex    = [];
        $completedByIndex = [];
        foreach ($names as $idx => $name) {
            $name = trim((string)$name);
            $raw  = strtoupper(trim((string)($statusesRaw[$idx] ?? 'NAO_INICIADO')));
            if (!in_array($raw, $allowedStatuses, true)) {
                $raw = 'NAO_INICIADO';
            }

            // Se a linha está completamente vazia, ignore nas validações
            if ($name === '' && empty($form['stage_id'][$idx] ?? null)) {
                continue;
            }

            $statusByIndex[$idx] = $raw;
            $completedByIndex[$idx] = isset($completedRaw[$idx]) ? 1 : 0;
        }

        if (!$statusByIndex) {
            return '';
        }

        // Depois, valida dependências
        foreach ($statusByIndex as $idx => $status) {
            $depIdxRaw = $dependsIndex[$idx] ?? null;
            if ($depIdxRaw === '' || $depIdxRaw === null) {
                continue; // sem dependência, etapa livre
            }

            $depIdx = (int)$depIdxRaw;
            if (!array_key_exists($depIdx, $statusByIndex)) {
                continue; // dependência inválida/fora da lista, não bloqueia
            }

            $depStatus = $statusByIndex[$depIdx] ?? 'NAO_INICIADO';

            // Status que indicam etapa "ativa" ou finalizada
            $statusAtivoOuFinal = [
                'EM_ANDAMENTO',
                'EM_VALIDACAO',
                'AGUARDANDO_APROVACAO',
                'BLOQUEADO',
                'CONCLUIDO',
            ];

            $estaConcluida = !empty($completedByIndex[$idx]) || $status === 'CONCLUIDO';

            // Regra:
            // - Não pode marcar como concluída (checkbox) nem avançar status para ativo/final
            //   se a etapa da qual depende ainda não estiver CONCLUIDO.
            if (($estaConcluida || in_array($status, $statusAtivoOuFinal, true)) && $depStatus !== 'CONCLUIDO') {
                return 'Existem etapas marcadas como concluídas ou em andamento com dependências não concluídas. Ajuste os status, o campo Concluído ou as dependências antes de salvar.';
            }
        }

        return '';
    }

    /**
     * Aplica um grupo de etapas padrão ao projeto (somente na memória, sem salvar).
     *
     * @param int $projectId
     * @return void
     */
    private function applyStageGroup(int $projectId): void
    {
        $form = $this->data['form'] ?? [];

        // Garante que o ID do projeto e a aba ativa estejam no form
        if (empty($form['id'])) {
            $form['id'] = $projectId;
        }
        $this->data['form'] = $form;
        $this->data['active_tab'] = $form['active_tab'] ?? 'etapas';

        $groupId = isset($form['stage_group_id']) ? (int)$form['stage_group_id'] : 0;
        if ($groupId <= 0) {
            $_SESSION['msg'] = 'Selecione um grupo de etapas para aplicar.';
            $_SESSION['msg_type'] = 'warning';

            // Recarrega as etapas atuais do projeto
            $stagesRepo = new ProjProjectStagesRepository();
            $rawStages = $stagesRepo->getByProject($projectId);
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
            return;
        }

        // Busca etapas do grupo selecionado
        $groupsRepo = new ProjStageGroupsRepository();
        $items = $groupsRepo->getItemsByGroup($groupId);

        if (empty($items)) {
            $_SESSION['msg'] = 'O grupo selecionado não possui etapas cadastradas.';
            $_SESSION['msg_type'] = 'warning';

            $this->data['stages'] = [];
            $this->view();
            return;
        }

        // Monta estrutura de etapas em memória e linhas para persistir no projeto
        $stages = [];
        $linesForProject = [];
        foreach ($items as $idx => $item) {
            $sequence = (int)($item['sequence'] ?? ($idx + 1));
            if ($sequence <= 0) {
                $sequence = $idx + 1;
            }

            $stageId = (int)($item['stage_id'] ?? 0);
            $name    = $item['stage_name'] ?? '';

            $stages[] = [
                'id'                  => null,
                'project_id'          => $projectId,
                'stage_id'            => $stageId,
                'name'                => $name,
                'activity'            => null,
                'description'         => null,
                'sequence'            => $sequence,
                'is_cost_stage'       => !empty($item['is_cost_stage']) ? 1 : 0,
                'status'              => 'NAO_INICIADO',
                'percent_complete'    => 0,
                'start_date'          => null,
                'expected_end_date'   => null,
                'end_date'            => null,
                'completed'           => 0,
                'responsible_user_id' => null,
                'depends_on_stage_id' => null,
                'depends_on_index'    => '',
                'responsible_name'    => null,
            ];

            $linesForProject[] = [
                'stage_id'            => $stageId,
                'name'                => $name,
                'sequence'            => $sequence,
                'start_date'          => null,
                'expected_end_date'   => null,
                'end_date'            => null,
                'activity'            => null,
                'description'         => null,
                'responsible_user_id' => null,
                'depends_on_index'    => null,
                'completed'           => 0,
                'is_cost_stage'       => !empty($item['is_cost_stage']) ? 1 : 0,
                'status'              => 'NAO_INICIADO',
                'percent_complete'    => 0,
            ];
        }

        // Persiste imediatamente as etapas do grupo no projeto,
        // sobrescrevendo quaisquer etapas anteriores
        $stagesRepo = new ProjProjectStagesRepository();
        $stagesRepo->replaceForProject($projectId, $linesForProject);

        // Recarrega as etapas salvas (para garantir consistência de IDs/dependências)
        $rawStages = $stagesRepo->getByProject($projectId);
        $idToIndex = [];
        foreach ($rawStages as $i => $row) {
            $idToIndex[(int)$row['id']] = $i;
        }
        foreach ($rawStages as $i => $row) {
            $rawStages[$i]['depends_on_index'] = isset($row['depends_on_stage_id'], $idToIndex[(int)$row['depends_on_stage_id']])
                ? (string)$idToIndex[(int)$row['depends_on_stage_id']] : '';
        }

        $this->data['stages'] = $rawStages;

        $_SESSION['msg'] = 'Etapas do projeto substituídas com sucesso a partir do grupo selecionado.';
        $_SESSION['msg_type'] = 'success';

        // Recarrega a tela mantendo a aba de etapas ativa
        $tab = urlencode($form['active_tab'] ?? 'etapas');
        header('Location: ' . $_ENV['URL_ADM'] . 'update-project/' . $projectId . '?tab=' . $tab);
        return;
    }
}

